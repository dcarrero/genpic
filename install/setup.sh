#!/bin/bash
# =============================================================================
# GenPic.com — Setup de producción con menú interactivo
# =============================================================================
# Uso:
#   ./setup.sh            # Menú interactivo
#   ./setup.sh --all      # Configurar todo de seguido
#   ./setup.sh --reset    # Borrar .env y empezar de cero
#   ./setup.sh --check    # Solo health check
#   ./setup.sh --help     # Ayuda
# =============================================================================

stty erase '^?' 2>/dev/null || true

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
DIM='\033[2m'
NC='\033[0m'

# Rutas
APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
WEBAPP_DIR="$(cd "$APP_DIR/.." && pwd)"
STORAGE_REAL="$WEBAPP_DIR/storage/storage"

# .env real (lo gestiona RunCloud, solo lectura)
ENV_FILE="$APP_DIR/.env"

# .env de referencia (lo genera setup.sh, para copiar al panel RunCloud)
ENV_DRAFT="${APP_DIR}/install/.env-for-deploy"

# Config persistente
SETUP_CONF="$APP_DIR/install/.setup_config"

# Cargar ruta personalizada del .env real si existe
if [ -f "$SETUP_CONF" ]; then
    SAVED_ENV_DIR=$(grep "^ENV_DIR=" "$SETUP_CONF" 2>/dev/null | cut -d'=' -f2-)
    [ -n "$SAVED_ENV_DIR" ] && ENV_FILE="${SAVED_ENV_DIR}/.env"
fi

# Primera ejecución: preguntar dónde está el .env real
if [ ! -f "$SETUP_CONF" ] && [ "$1" != "--help" ] && [ "$1" != "-h" ]; then
    echo ""
    echo -e "${BOLD}¿Dónde está el .env de RunCloud? (solo lectura)${NC}"
    echo -e "  ${DIM}RunCloud: /home/genpic/webapps/genpic/configs${NC}"
    echo -e "  ${DIM}Default:  $APP_DIR${NC}"
    echo ""
    read -e -p "  Ruta del directorio (Enter=default): " CUSTOM_ENV_DIR

    if [ -n "$CUSTOM_ENV_DIR" ]; then
        CUSTOM_ENV_DIR="${CUSTOM_ENV_DIR%/}"
        ENV_FILE="${CUSTOM_ENV_DIR}/.env"
        echo "ENV_DIR=${CUSTOM_ENV_DIR}" > "$SETUP_CONF"
    else
        echo "ENV_DIR=${APP_DIR}" > "$SETUP_CONF"
    fi
    echo -e "${GREEN}✓ Lee de: $ENV_FILE${NC}"
    echo -e "${GREEN}✓ Genera: $ENV_DRAFT${NC}"
    echo -e "  ${YELLOW}Copia el contenido de .env-for-deploy al panel RunCloud${NC}"
    echo ""
fi

# ═══════════════════════════════════════════════════════════════════════════════
# Funciones base
# ═══════════════════════════════════════════════════════════════════════════════

# Lee del .env real (RunCloud) primero, luego del draft
get_env() {
    local val=""
    if [ -f "$ENV_FILE" ]; then
        val=$(grep "^${1}=" "$ENV_FILE" 2>/dev/null | head -1 | cut -d'=' -f2- | sed 's/^"//' | sed 's/"$//')
    fi
    if [ -z "$val" ] && [ -f "$ENV_DRAFT" ]; then
        val=$(grep "^${1}=" "$ENV_DRAFT" 2>/dev/null | head -1 | cut -d'=' -f2- | sed 's/^"//' | sed 's/"$//')
    fi
    echo "$val"
}

# Escribe al fichero draft (.env-for-deploy) — nunca al .env real de RunCloud
set_env() {
    local key="$1" val="$2"
    [ ! -f "$ENV_DRAFT" ] && touch "$ENV_DRAFT"
    if grep -q "^${key}=" "$ENV_DRAFT" 2>/dev/null; then
        awk -v k="$key" -v v="$val" 'BEGIN{FS=OFS="="} $1==k{print k"="v;next}{print}' "$ENV_DRAFT" > "${ENV_DRAFT}.tmp"
        mv "${ENV_DRAFT}.tmp" "$ENV_DRAFT"
    else
        echo "${key}=${val}" >> "$ENV_DRAFT"
    fi
}

set_comment() {
    grep -qF "$1" "$ENV_FILE" 2>/dev/null || echo "$1" >> "$ENV_FILE"
}

mask() {
    local v="$1"
    if [ ${#v} -le 8 ]; then echo "****"; else echo "${v:0:4}...${v: -4}"; fi
}

ask_and_save() {
    local key="$1" prompt="$2" default="$3" secret="$4" quote="$5"
    local current val
    current=$(get_env "$key")

    if [ -n "$current" ]; then
        if [ "$secret" = "true" ]; then
            echo -e "  ${key} = ${YELLOW}$(mask "$current")${NC}" >&2
        else
            echo -e "  ${key} = ${YELLOW}${current}${NC}" >&2
        fi
        read -e -p "  ¿Cambiar? (Enter=mantener): " val
        val="${val:-$current}"
    else
        if [ "$secret" = "true" ]; then
            read -e -p "  $prompt: " val
        else
            read -e -p "  $prompt [$default]: " val
        fi
        val="${val:-$default}"
    fi

    [ "$quote" = "true" ] && set_env "$key" "\"$val\"" || set_env "$key" "$val"
}

show_section_values() {
    local keys="$1"
    for key in $keys; do
        local val=$(get_env "$key")
        if [ -n "$val" ]; then
            if echo "$key" | grep -qiE "KEY|SECRET|PASSWORD"; then
                echo -e "  ${DIM}${key}=${NC} ${YELLOW}$(mask "$val")${NC}"
            else
                echo -e "  ${DIM}${key}=${NC} ${YELLOW}${val}${NC}"
            fi
        else
            echo -e "  ${DIM}${key}=${NC} ${RED}(vacío)${NC}"
        fi
    done
}

# ═══════════════════════════════════════════════════════════════════════════════
# Secciones de configuración
# ═══════════════════════════════════════════════════════════════════════════════

config_app() {
    echo ""
    echo -e "${BOLD}── Aplicación ──${NC}"
    set_comment "# GenPic.com"
    set_env "APP_NAME" '"GenPic"'
    # APP_KEY debe existir (vacía) para que key:generate funcione
    local existing_key=$(get_env "APP_KEY")
    [ -z "$existing_key" ] && set_env "APP_KEY" ""
    set_env "APP_ENV" "production"
    set_env "APP_DEBUG" "false"
    set_env "APP_TIMEZONE" "Europe/Madrid"
    set_env "APP_LOCALE" "es"
    set_env "APP_FALLBACK_LOCALE" "es"
    set_env "APP_LOCALES" "es,en"
    set_env "APP_MAINTENANCE_DRIVER" "file"
    set_env "BCRYPT_ROUNDS" "12"
    ask_and_save "APP_URL" "URL del sitio" "https://tu-dominio.com"
    echo -e "${GREEN}  ✓ Aplicación guardada${NC}"
}

config_db() {
    echo ""
    echo -e "${BOLD}── Base de datos ──${NC}"
    set_env "DB_CONNECTION" "mysql"
    ask_and_save "DB_HOST" "Host BD" "127.0.0.1"
    ask_and_save "DB_PORT" "Puerto BD" "3306"
    ask_and_save "DB_DATABASE" "Nombre BD" "genpic"
    ask_and_save "DB_USERNAME" "Usuario BD" "genpic"
    ask_and_save "DB_PASSWORD" "Contraseña BD" "" "true" "true"
    echo -e "${GREEN}  ✓ Base de datos guardada${NC}"
}

config_redis() {
    echo ""
    echo -e "${BOLD}── Redis ──${NC}"
    set_env "SESSION_DRIVER" "redis"
    set_env "SESSION_LIFETIME" "120"
    set_env "SESSION_ENCRYPT" "false"
    set_env "SESSION_PATH" "/"
    set_env "SESSION_DOMAIN" "null"
    set_env "BROADCAST_CONNECTION" "log"
    set_env "FILESYSTEM_DISK" "wasabi"
    set_env "QUEUE_CONNECTION" "redis"
    set_env "CACHE_STORE" "redis"
    set_env "CACHE_PREFIX" "genpic_"
    set_env "REDIS_CLIENT" "phpredis"
    ask_and_save "REDIS_HOST" "Host Redis" "127.0.0.1"
    ask_and_save "REDIS_USERNAME" "Usuario Redis (null si sin ACL)" "null"
    ask_and_save "REDIS_PASSWORD" "Contraseña Redis (null si sin auth)" "null"
    set_env "REDIS_PORT" "6379"
    ask_and_save "REDIS_PREFIX" "Prefijo Redis" "genpic_"
    set_env "LOG_CHANNEL" "stack"
    set_env "LOG_STACK" "single"
    set_env "LOG_DEPRECATIONS_CHANNEL" "null"
    set_env "LOG_LEVEL" "warning"
    echo -e "${GREEN}  ✓ Redis guardado${NC}"
}

config_email() {
    echo ""
    echo -e "${BOLD}── Email (SMTP) ──${NC}"
    set_env "MAIL_MAILER" "smtp"
    set_env "MAIL_SCHEME" "null"
    ask_and_save "MAIL_HOST" "Host SMTP" "smtp.resend.com"
    ask_and_save "MAIL_PORT" "Puerto SMTP" "465"
    ask_and_save "MAIL_USERNAME" "Usuario SMTP" "" "true" "true"
    ask_and_save "MAIL_PASSWORD" "Contraseña SMTP" "" "true" "true"
    ask_and_save "MAIL_FROM_ADDRESS" "Email remitente" "hello@example.com" "" "true"
    set_env "MAIL_FROM_NAME" '"\${APP_NAME}"'
    echo -e "${GREEN}  ✓ Email guardado${NC}"
}

config_wasabi() {
    echo ""
    echo -e "${BOLD}── Wasabi S3 ──${NC}"
    ask_and_save "WASABI_KEY" "Access Key" "" "true"
    ask_and_save "WASABI_SECRET" "Secret Key" "" "true"
    ask_and_save "WASABI_REGION" "Región" "eu-central-1"
    ask_and_save "WASABI_BUCKET" "Bucket" ""
    local region=$(get_env "WASABI_REGION")
    set_env "WASABI_ENDPOINT" "https://s3.${region}.wasabisys.com"
    echo -e "${GREEN}  ✓ Wasabi guardado${NC}"
}

config_admin() {
    echo ""
    echo -e "${BOLD}── Administración ──${NC}"
    ask_and_save "ADMIN_EMAILS" "Emails admin (comas)" ""
    ask_and_save "ADMIN_ALLOWED_IPS" "IPs admin (vacío=sin restricción)" ""
    echo -e "${GREEN}  ✓ Admin guardado${NC}"
}

config_stripe() {
    echo ""
    echo -e "${BOLD}── Stripe ──${NC}"
    echo -e "  ${CYAN}Usa claves test (pk_test/sk_test) para pruebas${NC}" >&2
    ask_and_save "STRIPE_KEY" "Public Key (pk_...)" "" "true"
    ask_and_save "STRIPE_SECRET" "Secret Key (sk_...)" "" "true"
    ask_and_save "STRIPE_WEBHOOK_SECRET" "Webhook Secret (whsec_...)" "" "true"
    echo -e "${GREEN}  ✓ Stripe guardado${NC}"
}

config_ia() {
    echo ""
    echo -e "${BOLD}── Proveedores IA ──${NC}"
    ask_and_save "AI_PROVIDER_CHAIN" "Cadena de proveedores" "fal_ai,openrouter,novita"
    ask_and_save "FAL_AI_KEY" "fal.ai API Key" "" "true"
    ask_and_save "FAL_AI_ENABLED" "fal.ai activo (true/false)" "true"
    ask_and_save "OPENROUTER_API_KEY" "OpenRouter API Key (vacío si no)" "" "true"
    ask_and_save "OPENROUTER_ENABLED" "OpenRouter activo" "false"
    set_env "OPENROUTER_DEFAULT_MODEL" "bytedance-seed/seedream-4.5"
    ask_and_save "NOVITA_API_KEY" "Novita API Key (vacío si no)" "" "true"
    ask_and_save "NOVITA_ENABLED" "Novita activo" "false"
    ask_and_save "EVOLINK_API_KEY" "Evolink API Key (vacío si no)" "" "true"
    ask_and_save "EVOLINK_ENABLED" "Evolink activo" "false"
    set_env "UPSCALE_PROVIDER" "fal_ai"
    echo -e "${GREEN}  ✓ Proveedores IA guardados${NC}"
}

config_extras() {
    echo ""
    echo -e "${BOLD}── Configuración adicional ──${NC}"
    set_env "GENERATION_SESSION_TTL_DAYS" "14"
    set_env "DOWNLOAD_LINK_TTL_HOURS" "72"
    set_env "FILE_PURGE_GRACE_DAYS" "1"
    set_env "RATE_LIMIT_GENERATIONS_PER_IP" "3"
    set_env "RATE_LIMIT_PERIOD_HOURS" "24"
    ask_and_save "DAILY_COST_BUDGET_USD" "Presupuesto diario IA (USD)" "50"
    ask_and_save "CACHE_PUBLIC_PAGES_HOURS" "Cache páginas públicas (horas, 0=off)" "24"
    set_env "REQUIRE_PIN_VERIFICATION" "false"
    set_env "REQUIRE_EMAIL_BEFORE_GENERATION" "false"
    ask_and_save "MAINTENANCE_MODE" "Modo mantenimiento (true/false)" "true"
    set_env "VITE_APP_NAME" '"\${APP_NAME}"'
    echo -e "${GREEN}  ✓ Configuración adicional guardada${NC}"
}

config_all() {
    config_app
    config_db
    config_redis
    config_email
    config_wasabi
    config_admin
    config_stripe
    config_ia
    config_extras
}

# ═══════════════════════════════════════════════════════════════════════════════
# Post-config: APP_KEY, seed, assets, cache, check
# ═══════════════════════════════════════════════════════════════════════════════

run_post_config() {
    # APP_KEY
    local app_key=$(get_env "APP_KEY")
    if [ -z "$app_key" ] || [ "$app_key" = "" ]; then
        echo ""
        echo -e "${BOLD}Generando APP_KEY...${NC}"
        cd "$APP_DIR" && php artisan key:generate --force
        echo -e "${GREEN}✓ APP_KEY generada${NC}"
    else
        echo -e "${GREEN}✓ APP_KEY existente${NC}"
    fi

    # Storage
    echo ""
    echo -e "${BOLD}Estructura storage...${NC}"
    mkdir -p "$STORAGE_REAL/app/private/gallery"
    mkdir -p "$STORAGE_REAL/framework/cache/data"
    mkdir -p "$STORAGE_REAL/framework/sessions"
    mkdir -p "$STORAGE_REAL/framework/views"
    mkdir -p "$STORAGE_REAL/logs"
    echo -e "${GREEN}✓ Storage OK${NC}"

    # Seed
    echo ""
    read -e -p "  ¿Ejecutar migraciones + seeders? (S/n): " DO_SEED
    if [ "$DO_SEED" != "n" ] && [ "$DO_SEED" != "N" ]; then
        cd "$APP_DIR"
        local admin_email=$(get_env "ADMIN_EMAILS" | cut -d',' -f1)
        php artisan deploy:seed --admin-email="$admin_email"
    fi

    # Galería
    local GALLERY_SEED="$APP_DIR/install/gallery-seed"
    local GALLERY_DEST="$STORAGE_REAL/app/private/gallery"
    if [ -d "$GALLERY_SEED" ]; then
        echo ""
        echo -e "${BOLD}Copiando galería...${NC}"
        mkdir -p "$GALLERY_DEST"
        cp -Rn "$GALLERY_SEED/"* "$GALLERY_DEST/" 2>/dev/null || cp -R "$GALLERY_SEED/"* "$GALLERY_DEST/"
        echo -e "${GREEN}✓ $(find "$GALLERY_DEST" -type f | wc -l | tr -d ' ') imágenes${NC}"
    fi

    # Assets
    if command -v npm &> /dev/null; then
        echo ""
        read -e -p "  ¿Compilar assets (npm)? (S/n): " DO_BUILD
        if [ "$DO_BUILD" != "n" ] && [ "$DO_BUILD" != "N" ]; then
            cd "$APP_DIR" && npm ci && npm run build
            echo -e "${GREEN}✓ Assets compilados${NC}"
        fi
    fi

    # Storage link + cache
    echo ""
    cd "$APP_DIR"
    php artisan storage:link --force 2>/dev/null || true
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo -e "${GREEN}✓ Cache generada${NC}"
}

# ═══════════════════════════════════════════════════════════════════════════════
# Health check
# ═══════════════════════════════════════════════════════════════════════════════

run_check() {
    echo ""
    echo -e "${BOLD}═══ Health Check ═══${NC}"
    echo ""
    local ERRORS=0

    cd "$APP_DIR"

    # BD
    local db_ok=$(php artisan tinker --execute="try{DB::connection()->getPdo();echo 'ok';}catch(\Exception \$e){echo 'fail';}" 2>/dev/null | grep -o 'ok\|fail' | tail -1)
    if [ "$db_ok" = "ok" ]; then
        echo -e "  ${GREEN}✓${NC} Base de datos conecta"
        local styles=$(php artisan tinker --execute="echo \App\Models\ArtisticStyle::count();" 2>/dev/null | grep -oE '[0-9]+' | tail -1)
        local products=$(php artisan tinker --execute="echo \App\Models\Product::count();" 2>/dev/null | grep -oE '[0-9]+' | tail -1)
        local gallery=$(php artisan tinker --execute="echo \App\Models\GalleryExample::count();" 2>/dev/null | grep -oE '[0-9]+' | tail -1)
        local users=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | grep -oE '[0-9]+' | tail -1)
        echo -e "  ${GREEN}✓${NC} Datos: ${styles:-0} estilos, ${products:-0} productos, ${gallery:-0} galería, ${users:-0} usuarios"
        [ "${styles:-0}" = "0" ] && echo -e "  ${RED}✗ Sin estilos${NC}" && ERRORS=$((ERRORS+1))
        [ "${products:-0}" = "0" ] && echo -e "  ${RED}✗ Sin productos${NC}" && ERRORS=$((ERRORS+1))
    else
        echo -e "  ${RED}✗ Base de datos NO conecta${NC}"; ERRORS=$((ERRORS+1))
    fi

    # Redis
    local redis_ok=$(php artisan tinker --execute="try{\Illuminate\Support\Facades\Redis::ping();echo 'ok';}catch(\Exception \$e){echo 'fail';}" 2>/dev/null | grep -o 'ok\|fail' | tail -1)
    [ "$redis_ok" = "ok" ] && echo -e "  ${GREEN}✓${NC} Redis conecta" || { echo -e "  ${RED}✗ Redis NO conecta${NC}"; ERRORS=$((ERRORS+1)); }

    # Storage
    [ -d "$STORAGE_REAL/framework/views" ] && echo -e "  ${GREEN}✓${NC} Storage OK" || { echo -e "  ${RED}✗ Storage incompleto${NC}"; ERRORS=$((ERRORS+1)); }

    # Galería imágenes
    local gf=$(find "$STORAGE_REAL/app/private/gallery" -type f 2>/dev/null | wc -l | tr -d ' ')
    [ "${gf:-0}" -gt "0" ] 2>/dev/null && echo -e "  ${GREEN}✓${NC} Galería: $gf imágenes" || echo -e "  ${YELLOW}⚠${NC} Galería: sin imágenes (subir por SFTP)"

    # APP_KEY
    local ak=$(get_env "APP_KEY")
    [ -n "$ak" ] && echo -e "  ${GREEN}✓${NC} APP_KEY" || { echo -e "  ${RED}✗ APP_KEY vacía${NC}"; ERRORS=$((ERRORS+1)); }

    # Stripe
    local sk=$(get_env "STRIPE_KEY")
    [ -n "$sk" ] && echo -e "  ${GREEN}✓${NC} Stripe configurado" || echo -e "  ${YELLOW}⚠${NC} Stripe no configurado"

    # IA
    local fk=$(get_env "FAL_AI_KEY")
    [ -n "$fk" ] && echo -e "  ${GREEN}✓${NC} Proveedor IA configurado" || echo -e "  ${YELLOW}⚠${NC} Sin API key IA"

    # Mantenimiento
    local mm=$(get_env "MAINTENANCE_MODE")
    local ips=$(get_env "ADMIN_ALLOWED_IPS")
    [ "$mm" = "true" ] && echo -e "  ${YELLOW}⚠${NC} Mantenimiento ACTIVO — IPs: ${ips:-ninguna}" || echo -e "  ${GREEN}✓${NC} Sitio público"

    # Web
    local url=$(get_env "APP_URL")
    local http=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "$url" 2>/dev/null || echo "000")
    echo -e "  ${GREEN}✓${NC} Web: $url → HTTP $http"

    echo ""
    [ "$ERRORS" -gt "0" ] && echo -e "  ${RED}$ERRORS errores${NC}" || echo -e "  ${GREEN}Todo OK${NC}"
    echo ""
}

# ═══════════════════════════════════════════════════════════════════════════════
# Comparar .env real vs draft
# ═══════════════════════════════════════════════════════════════════════════════

compare_envs() {
    echo ""
    echo -e "${BOLD}═══ Comparación .env real vs draft ═══${NC}"
    echo ""

    if [ ! -f "$ENV_FILE" ]; then
        echo -e "  ${RED}✗ No se encuentra .env real: $ENV_FILE${NC}"
        return
    fi
    if [ ! -f "$ENV_DRAFT" ]; then
        echo -e "  ${YELLOW}⚠ No hay draft generado. Ejecuta una sección primero.${NC}"
        return
    fi

    echo -e "  ${DIM}Real:  $ENV_FILE${NC}"
    echo -e "  ${DIM}Draft: $ENV_DRAFT${NC}"
    echo ""

    local HAS_DIFF=0

    # Comparar cada clave del draft con el real
    while IFS= read -r line; do
        # Saltar comentarios y líneas vacías
        echo "$line" | grep -qE "^#|^$" && continue
        local key=$(echo "$line" | cut -d'=' -f1)
        local draft_val=$(echo "$line" | cut -d'=' -f2- | sed 's/^"//' | sed 's/"$//')
        local real_val=$(grep "^${key}=" "$ENV_FILE" 2>/dev/null | head -1 | cut -d'=' -f2- | sed 's/^"//' | sed 's/"$//')

        if [ -z "$real_val" ]; then
            echo -e "  ${RED}FALTA${NC}  ${key} ${DIM}(en draft: $(echo "$key" | grep -qiE 'KEY|SECRET|PASSWORD' && mask "$draft_val" || echo "$draft_val"))${NC}"
            HAS_DIFF=1
        elif [ "$real_val" != "$draft_val" ]; then
            if echo "$key" | grep -qiE "KEY|SECRET|PASSWORD"; then
                echo -e "  ${YELLOW}DIFF${NC}   ${key}: real=$(mask "$real_val") draft=$(mask "$draft_val")"
            else
                echo -e "  ${YELLOW}DIFF${NC}   ${key}: real=${real_val} draft=${draft_val}"
            fi
            HAS_DIFF=1
        fi
    done < "$ENV_DRAFT"

    # Claves en real que no están en draft
    while IFS= read -r line; do
        echo "$line" | grep -qE "^#|^$" && continue
        local key=$(echo "$line" | cut -d'=' -f1)
        if ! grep -q "^${key}=" "$ENV_DRAFT" 2>/dev/null; then
            echo -e "  ${CYAN}EXTRA${NC}  ${key} ${DIM}(en real, no en draft)${NC}"
            HAS_DIFF=1
        fi
    done < "$ENV_FILE"

    echo ""
    if [ "$HAS_DIFF" = "0" ]; then
        echo -e "  ${GREEN}✓ .env real y draft coinciden${NC}"
    else
        echo -e "  ${YELLOW}Copia las diferencias al panel RunCloud y ejecuta:${NC}"
        echo -e "  ${CYAN}php artisan config:cache${NC}"
    fi
    echo ""

    read -e -p "  ¿Ya actualizaste el .env en RunCloud? (s/N): " UPDATED
    if [ "$UPDATED" = "s" ] || [ "$UPDATED" = "S" ]; then
        echo -e "  ${GREEN}✓ Recacheando configuración...${NC}"
        cd "$APP_DIR" && php artisan config:cache
        echo -e "  ${GREEN}✓ Config cacheada${NC}"
    fi
}

# ═══════════════════════════════════════════════════════════════════════════════
# Menú principal
# ═══════════════════════════════════════════════════════════════════════════════

show_menu() {
    echo -e "${BOLD}${CYAN}"
    echo "╔══════════════════════════════════════════════╗"
    echo "║          GenPic.com — Setup                  ║"
    echo "╚══════════════════════════════════════════════╝"
    echo -e "${NC}"
    echo -e "  ${BOLD}Secciones de configuración:${NC}"
    echo ""
    echo -e "   ${CYAN}1${NC}  Aplicación (URL, entorno)"
    echo -e "   ${CYAN}2${NC}  Base de datos"
    echo -e "   ${CYAN}3${NC}  Redis"
    echo -e "   ${CYAN}4${NC}  Email (SMTP)"
    echo -e "   ${CYAN}5${NC}  Wasabi S3"
    echo -e "   ${CYAN}6${NC}  Administración (emails, IPs)"
    echo -e "   ${CYAN}7${NC}  Stripe (pagos)"
    echo -e "   ${CYAN}8${NC}  Proveedores IA"
    echo -e "   ${CYAN}9${NC}  Extras (límites, cache, mantenimiento)"
    echo ""
    echo -e "   ${CYAN}A${NC}  Configurar TODO"
    echo -e "   ${CYAN}D${NC}  Deploy (migraciones + seeders + assets + cache)"
    echo -e "   ${CYAN}C${NC}  Health check"
    echo -e "   ${CYAN}V${NC}  Ver .env actual (valores ofuscados)"
    echo -e "   ${CYAN}X${NC}  Comparar .env real vs draft (diferencias)"
    echo -e "   ${CYAN}Q${NC}  Salir"
    echo ""
    if [ -f "$ENV_DRAFT" ]; then
        echo -e "  ${DIM}Draft: $ENV_DRAFT${NC}"
    fi
    echo ""
}

show_env_summary() {
    echo ""
    echo -e "${BOLD}═══ .env actual ═══${NC}"
    echo ""
    echo -e "${BOLD}App:${NC}"
    show_section_values "APP_URL APP_ENV APP_DEBUG"
    echo -e "${BOLD}BD:${NC}"
    show_section_values "DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD"
    echo -e "${BOLD}Redis:${NC}"
    show_section_values "REDIS_HOST REDIS_USERNAME REDIS_PASSWORD REDIS_PREFIX"
    echo -e "${BOLD}Email:${NC}"
    show_section_values "MAIL_HOST MAIL_PORT MAIL_USERNAME MAIL_PASSWORD MAIL_FROM_ADDRESS"
    echo -e "${BOLD}Wasabi:${NC}"
    show_section_values "WASABI_KEY WASABI_SECRET WASABI_REGION WASABI_BUCKET"
    echo -e "${BOLD}Admin:${NC}"
    show_section_values "ADMIN_EMAILS ADMIN_ALLOWED_IPS"
    echo -e "${BOLD}Stripe:${NC}"
    show_section_values "STRIPE_KEY STRIPE_SECRET STRIPE_WEBHOOK_SECRET"
    echo -e "${BOLD}IA:${NC}"
    show_section_values "FAL_AI_KEY FAL_AI_ENABLED OPENROUTER_API_KEY OPENROUTER_ENABLED NOVITA_API_KEY NOVITA_ENABLED EVOLINK_API_KEY EVOLINK_ENABLED"
    echo -e "${BOLD}Extras:${NC}"
    show_section_values "MAINTENANCE_MODE CACHE_PUBLIC_PAGES_HOURS DAILY_COST_BUDGET_USD"
    echo ""
}

# ═══════════════════════════════════════════════════════════════════════════════
# Argumentos CLI
# ═══════════════════════════════════════════════════════════════════════════════

if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo ""
    echo -e "${BOLD}GenPic.com — Setup de producción${NC}"
    echo ""
    echo "  ./setup.sh              Menú interactivo"
    echo "  ./setup.sh --all        Configurar todo de seguido"
    echo "  ./setup.sh --reset      Borrar .env (preserva APP_KEY)"
    echo "  ./setup.sh --check      Solo health check"
    echo "  ./setup.sh --help       Esta ayuda"
    echo ""
    echo "  App:      $APP_DIR"
    echo "  Storage:  $STORAGE_REAL"
    echo "  .env:     $ENV_FILE"
    echo ""
    exit 0
fi

if [ "$1" = "--reset" ]; then
    echo ""
    if [ -f "$ENV_FILE" ]; then
        echo -e "${YELLOW}⚠ BORRAR .env en $ENV_FILE${NC}"
        read -e -p "  Escribe 'RESET': " CONFIRM
        [ "$CONFIRM" != "RESET" ] && echo "Cancelado." && exit 1
        PRESERVED_KEY=$(grep "^APP_KEY=" "$ENV_FILE" 2>/dev/null | head -1 | cut -d'=' -f2-)
        rm -f "$ENV_FILE"
        echo -e "${GREEN}✓ .env eliminado${NC}"
        if [ -n "$PRESERVED_KEY" ]; then
            echo "APP_KEY=$PRESERVED_KEY" > "$ENV_FILE"
            echo -e "${GREEN}✓ APP_KEY preservada${NC}"
        fi
    fi
    echo ""
fi

if [ "$1" = "--check" ]; then
    run_check; exit 0
fi

if [ "$1" = "--all" ]; then
    echo -e "${BOLD}${CYAN}"
    echo "╔══════════════════════════════════════════════╗"
    echo "║     GenPic.com — Configuración completa      ║"
    echo "╚══════════════════════════════════════════════╝"
    echo -e "${NC}"
    config_all
    run_post_config
    run_check
    exit 0
fi

# ═══════════════════════════════════════════════════════════════════════════════
# Loop del menú
# ═══════════════════════════════════════════════════════════════════════════════

while true; do
    show_menu
    read -e -p "  Opción: " CHOICE

    case "$CHOICE" in
        1) config_app ;;
        2) config_db ;;
        3) config_redis ;;
        4) config_email ;;
        5) config_wasabi ;;
        6) config_admin ;;
        7) config_stripe ;;
        8) config_ia ;;
        9) config_extras ;;
        [aA]) config_all ;;
        [dD]) run_post_config ;;
        [cC]) run_check ;;
        [vV]) show_env_summary ;;
        [xX]) compare_envs ;;
        [qQ]) echo -e "${GREEN}¡Hasta luego!${NC}"; exit 0 ;;
        *) echo -e "${RED}Opción no válida${NC}" ;;
    esac

    echo ""
    read -e -p "  Pulsa Enter para volver al menú..." _
done
