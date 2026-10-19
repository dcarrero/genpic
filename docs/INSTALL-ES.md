# Instalación

[Read in English](INSTALL.md)

Cómo poner GenPic en marcha en tu máquina. Para un servidor de producción
mira [DEPLOY.md](DEPLOY.md) y para cada variable de configuración,
[CONFIGURATION.md](CONFIGURATION.md) (los dos en inglés).

## Con Docker (recomendado)

Solo necesitas Docker con Compose v2. Se levantan cinco contenedores: la app
(PHP 8.4 con `php artisan serve`), Horizon para las colas, MariaDB 10.11,
Redis 7 y Mailpit.

### 1. Arrancarlo

```bash
git clone https://github.com/dcarrero/genpic.git
cd genpic
cp .env.example .env
docker compose up -d --build
```

El primer arranque tarda unos minutos porque el contenedor `app` ejecuta
`composer install`, `npm install && npm run build` y
`php artisan migrate --seed` antes de empezar a servir. Puedes seguirlo con:

```bash
docker compose logs -f app
```

Cuando veas que terminan los seeders y arranca el servidor, genera la clave de
la aplicación:

```bash
docker compose exec app php artisan key:generate
```

| Qué | Dónde |
|-----|-------|
| Web | http://localhost:8101 |
| Mailpit (todos los emails que manda la app) | http://localhost:8025 |
| Panel de administración | http://localhost:8101/admin |
| Horizon (colas) | http://localhost:8101/horizon |
| MariaDB desde tu equipo | `127.0.0.1:33061`, usuario `genpic`, contraseña `secret` |
| Redis desde tu equipo | `127.0.0.1:63791` |

La siembra carga 73 estilos en nueve categorías, los productos, 36 ejemplos
de galería, testimonios y secuencias de email. Cada arranque vuelve a lanzar
`migrate --seed`, y no pasa nada porque los seeders se pueden repetir sin
duplicar datos.

### 2. Crear el usuario administrador

Quien compra no tiene contraseña, así que el admin se crea a mano.
`.env.example` ya trae `admin@example.com` en `ADMIN_EMAILS`:

```bash
docker compose exec app php artisan tinker --execute="\$u = App\Models\User::firstOrNew(['email' => 'admin@example.com']); \$u->forceFill(['password' => bcrypt('change-me'), 'is_admin' => true])->save();"
```

Entra en http://localhost:8101/admin con `admin@example.com` y `change-me`. Si
prefieres otro email, añádelo también a `ADMIN_EMAILS`.

### 3. Generar retratos

Sin claves de IA puedes recorrer toda la web, pero la generación falla. Para
generar en local:

1. Añade `FAL_AI_KEY` al `.env`. En local hace falta aunque genere otro
   proveedor, porque la foto se sube a la CDN de fal.ai para tener una URL que
   el proveedor pueda descargar. `OPENROUTER_API_KEY` es opcional (Docker
   activa OpenRouter).
2. Pon un `HUGGINGFACE_TOKEN` o `CONTENT_MODERATION_ENABLED=false`. Sin token
   la comprobación NSFW se salta igualmente.
3. Recrea los contenedores para que cojan las claves (Compose pasa
   `FAL_AI_KEY` como variable de entorno):

   ```bash
   docker compose up -d
   ```

Cada retrato cuesta unos céntimos en el proveedor. Tienes la tabla de precios
en el [README](../README-ES.md#servicios-externos-y-lo-que-cuestan).

### 4. Pagos

Para probar el checkout sin Stripe, **vacía** las tres líneas de Stripe del
`.env`:

```
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

Sin clave secreta el pago se simula: el pedido se marca como pagado al
momento y los formatos se generan como si Stripe lo hubiera confirmado. Los
valores de ejemplo de `.env.example` (`sk_test_...`) no están vacíos, así que
con ellos la app intentaría llamar a Stripe y fallaría.

Para probar Stripe de verdad usa tus claves de test, activa Stripe Tax en modo
test y redirige el webhook con la CLI de Stripe:

```bash
stripe listen --forward-to localhost:8101/webhooks/stripe
```

El `whsec_...` que te muestra va en `STRIPE_WEBHOOK_SECRET`.

### 5. Tareas programadas

El stack de Compose no lanza el programador de tareas. Solo te hace falta para
probar la purga nocturna, los emails de caducidad y de abandono o la
conciliación con Stripe:

```bash
docker compose exec app php artisan schedule:work
```

### 6. Imágenes a tamaño completo (opcional)

El repo trae las muestras de estilo a 1200 px para que el clone pese poco. El
juego original de 2048 px (unos 340 MB) está publicado como asset de la
release:

```bash
./scripts/fetch-samples.sh
```

El script sobrescribe `public/img/` con el juego completo. Si quieres
descargarlo de otro sitio, define `GENPIC_IMAGES_URL`.

## Sin Docker

Necesitas:

- PHP 8.3 u 8.4 con `bcmath`, `gd`, `imagick`, `intl`, `pcntl`, `pdo_mysql`,
  `redis` y `zip`. Con `imagick` (y ImageMagick) se hacen la marca de agua y
  los formatos de descarga; `pcntl` lo pide Horizon.
- Composer 2
- Node.js 20+ y npm
- MariaDB 10.11+ (MySQL 8 debería valer, pero producción iba con MariaDB)
- Redis 7+

Después:

```bash
git clone https://github.com/dcarrero/genpic.git
cd genpic
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Edita el `.env` para que apunte a tus servicios locales (por defecto trae los
nombres de host de Docker):

```
APP_URL=http://localhost:8000
DB_HOST=127.0.0.1
DB_DATABASE=genpic
DB_USERNAME=your_user
DB_PASSWORD=your_password
REDIS_HOST=127.0.0.1
```

Crea la base de datos (`utf8mb4`) y luego:

```bash
php artisan migrate --seed
php artisan serve                  # http://localhost:8000
php artisan horizon                # colas, en otra terminal
php artisan schedule:work          # opcional, en otra terminal
```

La generación va por la cola, así que sin Horizon (o `php artisan
queue:work`) la vista previa se queda en «generando» para siempre. Los emails
acaban en `storage/logs/laravel.log`, porque `.env.example` trae
`MAIL_MAILER=log`.

Si vas a tocar el front, `npm run dev` te da Vite con recarga en caliente en
lugar de `npm run build`. Con `composer dev` arrancas a la vez el servidor, un
listener de la cola, el visor de logs y Vite.

Los pasos 2 a 6 de Docker también valen aquí, quitando el prefijo
`docker compose exec app`.

## Ejecutar los tests

Los tests corren sobre SQLite en memoria y con Redis simulado, así que no
necesitan ni MariaDB ni Redis. Lo que sí necesitan es una `APP_KEY`:

```bash
cp .env.example .env               # si aún no lo tienes
php artisan key:generate
php artisan test
```

o con Docker:

```bash
docker compose exec app php artisan test
```

Tienen que pasar los 178. La CI los ejecuta con PHP 8.4 en cada push
(`.github/workflows/tests.yml`).

## Problemas habituales

**La web no responde en el puerto 8101 justo después del `up`.** El primer
arranque sigue instalando dependencias. Míralo con
`docker compose logs -f app`.

**`docker compose ps` muestra `queue` reiniciándose.** El contenedor de
Horizon comparte el código con `app` y se cae hasta que `composer install` ha
creado `vendor/`. Se recupera solo y, si no, ejecuta
`docker compose restart queue`.

**«The container name /genpic-app is already in use».** Los contenedores
tienen nombre fijo (`genpic-app`, `genpic-db`…). Borra los antiguos o cambia
`container_name` en `docker-compose.yml`.

**Un puerto ya está ocupado.** Cambia el lado del host en
`docker-compose.yml` (`"8101:8000"`, `"8025:8025"`…). Si cambias el de la app,
cambia también `APP_URL` en `docker-compose.yml`.

**«No application encryption key has been specified».** Ejecuta
`php artisan key:generate` (con el prefijo `docker compose exec app` si usas
Docker).

**La vista previa no termina nunca.** La generación es un job en cola.
Comprueba que Horizon está en marcha (`/horizon`), que la clave del proveedor
está puesta y que recreaste los contenedores después de añadirla. Los fallos
quedan en `storage/logs/laravel.log` y en el admin, en
*Sistema → Logs de IA*.

**«fal.ai key required for CDN upload in local environment».** Falta
`FAL_AI_KEY`; mira el paso 3.

**Las fichas de estilo salen sin imagen.** Faltan las imágenes de ese estilo
en `public/img/styles/`. Ejecuta `./scripts/fetch-samples.sh` o genéralas como
se explica en [CUSTOMIZING.md](CUSTOMIZING.md#add-an-artistic-style) (en
inglés).

**No se ven los cambios en traducciones o estilos.** Las páginas públicas se
guardan en caché durante `CACHE_PUBLIC_PAGES_HOURS`. Ejecuta
`php artisan optimize:clear` o pon la variable a `0` mientras trabajas.
