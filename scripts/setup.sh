#!/bin/bash
# =============================================================================
# GenPic.com — Setup inicial de producción
# =============================================================================
# Este script se ejecuta UNA VEZ en el primer deploy.
# Si se ejecuta de nuevo, NO sobreescribe valores existentes en .env
#
# Ubicación recomendada: fuera del webroot
#   /home/runcloud/webapps/genpic/setup.sh
#
# Uso:
#   chmod +x setup.sh
#   ./setup.sh
# =============================================================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# Rutas
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_DIR="$SCRIPT_DIR/current"
SHARED_DIR="$SCRIPT_DIR/shared"
ENV_FILE="$SHARED_DIR/.env"

echo -e "${BOLD}${CYAN}"
echo "╔══════════════════════════════════════════════╗"
echo "║          GenPic.com — Setup Inicial          ║"
echo "╚══════════════════════════════════════════════╝"
echo -e "${NC}"

# ─── Verificar estructura ────────────────────────────────────────────────────

if [ ! -d "$APP_DIR" ]; then
    echo -e "${YELLOW}⚠ No se encuentra $APP_DIR${NC}"
    echo "  Asegúrate de que el primer deploy de RunCloud se ha ejecutado."
    echo "  El directorio 'current' debe ser un symlink al release activo."
    echo ""
    read -p "¿Ruta alternativa al código Laravel? (vacío para cancelar): " ALT_PATH
    if [ -z "$ALT_PATH" ]; then
        echo "Cancelado."
        exit 1
    fi
    APP_DIR="$ALT_PATH"
fi

if [ ! -f "$APP_DIR/artisan" ]; then
    echo -e "${RED}✗ No se encuentra artisan en $APP_DIR${NC}"
    exit 1
fi

echo -e "${GREEN}✓ App encontrada: $APP_DIR${NC}"

# ─── Crear estructura shared ─────────────────────────────────────────────────

echo ""
echo -e "${BOLD}1. Preparando estructura shared...${NC}"

mkdir -p "$SHARED_DIR/storage/app/gallery"
mkdir -p "$SHARED_DIR/storage/framework/cache/data"
mkdir -p "$SHARED_DIR/storage/framework/sessions"
mkdir -p "$SHARED_DIR/storage/framework/views"
mkdir -p "$SHARED_DIR/storage/logs"

echo -e "${GREEN}✓ Directorios shared creados${NC}"

# ─── Función auxiliar para pedir valores ──────────────────────────────────────

# Lee valor actual de .env si existe
get_env_value() {
    local key="$1"
    if [ -f "$ENV_FILE" ]; then
        grep "^${key}=" "$ENV_FILE" 2>/dev/null | head -1 | cut -d'=' -f2- | sed 's/^"//' | sed 's/"$//'
    fi
}

# Pide un valor al usuario, mostrando el actual si existe
ask_value() {
    local key="$1"
    local prompt="$2"
    local default="$3"
    local secret="$4"
    local current
    current=$(get_env_value "$key")

    if [ -n "$current" ]; then
        if [ "$secret" = "true" ]; then
            local masked="${current:0:4}...${current: -4}"
            echo -e "  ${key} = ${YELLOW}${masked}${NC} (ya configurado)"
            read -p "  ¿Cambiar? (Enter para mantener, nuevo valor para cambiar): " new_val
        else
            echo -e "  ${key} = ${YELLOW}${current}${NC}"
            read -p "  ¿Cambiar? (Enter para mantener): " new_val
        fi
        if [ -z "$new_val" ]; then
            echo "$current"
            return
        fi
        echo "$new_val"
        return
    fi

    if [ "$secret" = "true" ]; then
        read -p "  $prompt: " new_val
    else
        read -p "  $prompt [$default]: " new_val
    fi
    echo "${new_val:-$default}"
}

# ─── Configuración interactiva ────────────────────────────────────────────────

echo ""
echo -e "${BOLD}2. Configuración del entorno (.env)${NC}"
echo -e "   ${CYAN}Los valores existentes se mantienen si pulsas Enter${NC}"
echo ""

# --- App ---
echo -e "${BOLD}── Aplicación ──${NC}"
APP_URL=$(ask_value "APP_URL" "URL del sitio (https://tu-dominio.com)" "")

# --- BD ---
echo ""
echo -e "${BOLD}── Base de datos ──${NC}"
DB_HOST=$(ask_value "DB_HOST" "Host BD" "127.0.0.1")
DB_PORT=$(ask_value "DB_PORT" "Puerto BD" "3306")
DB_DATABASE=$(ask_value "DB_DATABASE" "Nombre BD" "genpic")
DB_USERNAME=$(ask_value "DB_USERNAME" "Usuario BD" "genpic_user")
DB_PASSWORD=$(ask_value "DB_PASSWORD" "Contraseña BD" "" "true")

# --- Redis ---
echo ""
echo -e "${BOLD}── Redis ──${NC}"
REDIS_HOST=$(ask_value "REDIS_HOST" "Host Redis" "127.0.0.1")
REDIS_PASSWORD=$(ask_value "REDIS_PASSWORD" "Contraseña Redis (vacío si sin auth)" "null")

# --- Email ---
echo ""
echo -e "${BOLD}── Email (SMTP) ──${NC}"
MAIL_HOST=$(ask_value "MAIL_HOST" "Host SMTP" "smtp.resend.com")
MAIL_PORT=$(ask_value "MAIL_PORT" "Puerto SMTP" "465")
MAIL_USERNAME=$(ask_value "MAIL_USERNAME" "Usuario SMTP" "" "true")
MAIL_PASSWORD=$(ask_value "MAIL_PASSWORD" "Contraseña SMTP" "" "true")
MAIL_FROM_ADDRESS=$(ask_value "MAIL_FROM_ADDRESS" "Email remitente" "hello@example.com")

# --- Wasabi S3 ---
echo ""
echo -e "${BOLD}── Wasabi S3 (almacenamiento imágenes) ──${NC}"
WASABI_KEY=$(ask_value "WASABI_KEY" "Access Key" "" "true")
WASABI_SECRET=$(ask_value "WASABI_SECRET" "Secret Key" "" "true")
WASABI_REGION=$(ask_value "WASABI_REGION" "Región" "eu-central-1")
WASABI_BUCKET=$(ask_value "WASABI_BUCKET" "Bucket" "")

# --- Admin ---
echo ""
echo -e "${BOLD}── Administración ──${NC}"
ADMIN_EMAILS=$(ask_value "ADMIN_EMAILS" "Emails admin (separados por comas)" "")
ADMIN_ALLOWED_IPS=$(ask_value "ADMIN_ALLOWED_IPS" "IPs admin (vacío = sin restricción)" "")

# --- Stripe ---
echo ""
echo -e "${BOLD}── Stripe (pagos) ──${NC}"
echo -e "  ${CYAN}Usa claves test (pk_test/sk_test) para pruebas${NC}"
STRIPE_KEY=$(ask_value "STRIPE_KEY" "Public Key (pk_...)" "" "true")
STRIPE_SECRET=$(ask_value "STRIPE_SECRET" "Secret Key (sk_...)" "" "true")
STRIPE_WEBHOOK_SECRET=$(ask_value "STRIPE_WEBHOOK_SECRET" "Webhook Secret (whsec_...)" "" "true")

# --- IA ---
echo ""
echo -e "${BOLD}── Proveedores IA ──${NC}"
AI_PROVIDER_CHAIN=$(ask_value "AI_PROVIDER_CHAIN" "Cadena de proveedores" "fal_ai,openrouter,novita")
FAL_AI_KEY=$(ask_value "FAL_AI_KEY" "fal.ai API Key" "" "true")
FAL_AI_ENABLED=$(ask_value "FAL_AI_ENABLED" "fal.ai activo (true/false)" "true")
OPENROUTER_API_KEY=$(ask_value "OPENROUTER_API_KEY" "OpenRouter API Key (vacío si no)" "" "true")
OPENROUTER_ENABLED=$(ask_value "OPENROUTER_ENABLED" "OpenRouter activo" "false")
NOVITA_API_KEY=$(ask_value "NOVITA_API_KEY" "Novita API Key (vacío si no)" "" "true")
NOVITA_ENABLED=$(ask_value "NOVITA_ENABLED" "Novita activo" "false")
EVOLINK_API_KEY=$(ask_value "EVOLINK_API_KEY" "Evolink API Key (vacío si no)" "" "true")
EVOLINK_ENABLED=$(ask_value "EVOLINK_ENABLED" "Evolink activo" "false")

# --- Caché páginas ---
echo ""
echo -e "${BOLD}── Configuración adicional ──${NC}"
CACHE_PUBLIC_PAGES_HOURS=$(ask_value "CACHE_PUBLIC_PAGES_HOURS" "Cache páginas públicas (horas, 0=off)" "24")
DAILY_COST_BUDGET_USD=$(ask_value "DAILY_COST_BUDGET_USD" "Presupuesto diario IA (USD)" "50")

# ─── Generar .env ─────────────────────────────────────────────────────────────

echo ""
echo -e "${BOLD}3. Generando .env...${NC}"

# Preservar APP_KEY si ya existe
EXISTING_APP_KEY=$(get_env_value "APP_KEY")

cat > "$ENV_FILE" << ENVEOF
# ===========================================
# GenPic.com — Producción
# Generado por setup.sh el $(date '+%Y-%m-%d %H:%M')
# ===========================================

APP_NAME="GenPic"
APP_ENV=production
APP_KEY=${EXISTING_APP_KEY:-}
APP_DEBUG=false
APP_TIMEZONE=Europe/Madrid
APP_URL=${APP_URL}
# APP_VERSION se lee de config/app.php (viaja con el código, no en .env)

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES
APP_LOCALES=es,en

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

# -------------------------------------------
# Logging
# -------------------------------------------
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

# -------------------------------------------
# Base de datos
# -------------------------------------------
DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD="${DB_PASSWORD}"

# -------------------------------------------
# Sesión y Cache — Redis
# -------------------------------------------
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=wasabi
QUEUE_CONNECTION=redis

CACHE_STORE=redis
CACHE_PREFIX=genpic_

REDIS_CLIENT=phpredis
REDIS_HOST=${REDIS_HOST}
REDIS_PASSWORD=${REDIS_PASSWORD}
REDIS_PORT=6379

# -------------------------------------------
# Email
# -------------------------------------------
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=${MAIL_HOST}
MAIL_PORT=${MAIL_PORT}
MAIL_USERNAME="${MAIL_USERNAME}"
MAIL_PASSWORD="${MAIL_PASSWORD}"
MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS}"
MAIL_FROM_NAME="\${APP_NAME}"

# -------------------------------------------
# Wasabi S3
# -------------------------------------------
WASABI_KEY=${WASABI_KEY}
WASABI_SECRET=${WASABI_SECRET}
WASABI_REGION=${WASABI_REGION}
WASABI_BUCKET=${WASABI_BUCKET}
WASABI_ENDPOINT=https://s3.${WASABI_REGION}.wasabisys.com

# -------------------------------------------
# Admin
# -------------------------------------------
ADMIN_EMAILS=${ADMIN_EMAILS}
ADMIN_ALLOWED_IPS=${ADMIN_ALLOWED_IPS}

# -------------------------------------------
# Stripe
# -------------------------------------------
STRIPE_KEY=${STRIPE_KEY}
STRIPE_SECRET=${STRIPE_SECRET}
STRIPE_WEBHOOK_SECRET=${STRIPE_WEBHOOK_SECRET}

# -------------------------------------------
# Proveedores IA
# -------------------------------------------
AI_PROVIDER_CHAIN=${AI_PROVIDER_CHAIN}

FAL_AI_KEY=${FAL_AI_KEY}
FAL_AI_ENABLED=${FAL_AI_ENABLED}

OPENROUTER_API_KEY=${OPENROUTER_API_KEY}
OPENROUTER_ENABLED=${OPENROUTER_ENABLED}
OPENROUTER_DEFAULT_MODEL=bytedance-seed/seedream-4.5

EVOLINK_API_KEY=${EVOLINK_API_KEY}
EVOLINK_ENABLED=${EVOLINK_ENABLED}

NOVITA_API_KEY=${NOVITA_API_KEY}
NOVITA_ENABLED=${NOVITA_ENABLED}

UPSCALE_PROVIDER=fal_ai

# -------------------------------------------
# Tiempos y límites
# -------------------------------------------
GENERATION_SESSION_TTL_DAYS=14
DOWNLOAD_LINK_TTL_HOURS=72
FILE_PURGE_GRACE_DAYS=1
RATE_LIMIT_GENERATIONS_PER_IP=3
RATE_LIMIT_PERIOD_HOURS=24
DAILY_COST_BUDGET_USD=${DAILY_COST_BUDGET_USD}
CACHE_PUBLIC_PAGES_HOURS=${CACHE_PUBLIC_PAGES_HOURS}

REQUIRE_PIN_VERIFICATION=false
REQUIRE_EMAIL_BEFORE_GENERATION=false

# Mantenimiento (true = 503 para todos excepto ADMIN_ALLOWED_IPS)
MAINTENANCE_MODE=true

# -------------------------------------------
# Vite
# -------------------------------------------
VITE_APP_NAME="\${APP_NAME}"
ENVEOF

echo -e "${GREEN}✓ .env generado en $ENV_FILE${NC}"

# ─── APP_KEY ──────────────────────────────────────────────────────────────────

if [ -z "$EXISTING_APP_KEY" ]; then
    echo ""
    echo -e "${BOLD}4. Generando APP_KEY...${NC}"

    # Crear symlink temporal para que artisan pueda leer el .env
    ln -nfs "$ENV_FILE" "$APP_DIR/.env"

    cd "$APP_DIR"
    php artisan key:generate --force
    echo -e "${GREEN}✓ APP_KEY generada${NC}"
else
    echo -e "${GREEN}✓ APP_KEY existente preservada${NC}"
fi

# ─── Symlinks ─────────────────────────────────────────────────────────────────

echo ""
echo -e "${BOLD}5. Configurando symlinks...${NC}"

cd "$APP_DIR"
ln -nfs "$ENV_FILE" .env
rm -rf storage/app storage/framework storage/logs 2>/dev/null || true
ln -nfs "$SHARED_DIR/storage/app" storage/app
ln -nfs "$SHARED_DIR/storage/framework" storage/framework
ln -nfs "$SHARED_DIR/storage/logs" storage/logs

php artisan storage:link --force 2>/dev/null || true

echo -e "${GREEN}✓ Symlinks configurados${NC}"

# ─── Deploy seed ──────────────────────────────────────────────────────────────

echo ""
echo -e "${BOLD}6. Seed inicial de base de datos${NC}"
echo -e "   ${CYAN}Migraciones + estilos + productos + galería + testimonios + admin${NC}"
echo ""

read -p "  ¿Ejecutar deploy:seed ahora? (S/n): " DO_SEED

if [ "$DO_SEED" != "n" ] && [ "$DO_SEED" != "N" ]; then
    cd "$APP_DIR"
    php artisan deploy:seed --admin-email="$(echo $ADMIN_EMAILS | cut -d',' -f1)"
else
    echo -e "${YELLOW}⚠ Seed omitido. Ejecútalo manualmente:${NC}"
    echo "  cd $APP_DIR && php artisan deploy:seed --admin-email=tu@email.com"
fi

# ─── Assets ───────────────────────────────────────────────────────────────────

echo ""
echo -e "${BOLD}7. Build de assets${NC}"

if command -v npm &> /dev/null; then
    read -p "  ¿Compilar assets ahora? (S/n): " DO_BUILD
    if [ "$DO_BUILD" != "n" ] && [ "$DO_BUILD" != "N" ]; then
        cd "$APP_DIR"
        npm ci && npm run build
        echo -e "${GREEN}✓ Assets compilados${NC}"
    else
        echo -e "${YELLOW}⚠ Build omitido${NC}"
    fi
else
    echo -e "${YELLOW}⚠ npm no disponible en el servidor${NC}"
    echo -e "  Los assets deben compilarse en local y subirse con el deploy."
    echo -e "  En tu máquina: ${CYAN}npm run build${NC}"
    echo -e "  La carpeta ${CYAN}public/build/${NC} debe estar en el repositorio o copiarse manualmente."
fi

# ─── Cache ────────────────────────────────────────────────────────────────────

echo ""
echo -e "${BOLD}8. Cacheando configuración...${NC}"

cd "$APP_DIR"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "${GREEN}✓ Cache generada${NC}"

# ─── Resumen ──────────────────────────────────────────────────────────────────

echo ""
echo -e "${BOLD}${GREEN}═══════════════════════════════════════════════${NC}"
echo -e "${BOLD}${GREEN}  ✓ Setup completado${NC}"
echo -e "${BOLD}${GREEN}═══════════════════════════════════════════════${NC}"
echo ""
echo -e "  ${BOLD}Próximos pasos:${NC}"
echo ""
echo -e "  1. ${CYAN}Copiar imágenes de galería${NC} al servidor:"
echo -e "     rsync -avz storage/app/gallery/ $SHARED_DIR/storage/app/gallery/"
echo ""
echo -e "  2. ${CYAN}Configurar Stripe webhook${NC} en dashboard.stripe.com:"
echo -e "     URL: ${APP_URL}/webhooks/stripe"
echo -e "     Eventos: checkout.session.completed, checkout.session.async_payment_succeeded"
echo ""
echo -e "  3. ${CYAN}Configurar cron${NC} en RunCloud:"
echo -e "     * * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
echo ""
echo -e "  4. ${CYAN}Configurar Horizon${NC} en RunCloud Supervisor:"
echo -e "     command=php $APP_DIR/artisan horizon"
echo ""
echo -e "  5. ${CYAN}Proteger con auth básica${NC} del servidor web para pruebas"
echo ""
echo -e "  6. ${CYAN}Probar flujo completo${NC}: subir foto → generar → pagar → descargar"
echo ""
echo -e "  ${YELLOW}Para re-ejecutar este setup sin perder datos:${NC}"
echo -e "  Los valores existentes se mantienen si pulsas Enter."
echo ""
