# CMS API (Laravel)

API REST para gestión modular de **artículos**, **categorías** y **usuarios**, con autenticación por token (Laravel Sanctum), validaciones de negocio y persistencia en MySQL.

## Requisitos

- [Docker](https://docs.docker.com/get-docker/) y Docker Compose v2

No es necesario tener PHP ni Composer instalados en el host: el contenedor `app` instala dependencias con Composer y ejecuta las migraciones al iniciar.

## Puesta en marcha con Docker

Desde la raíz del proyecto:

```bash
cd cms-api
docker compose up -d --build
```

La API queda disponible en **http://localhost:8080** (puerto configurable con la variable `APP_PORT`).

En el primer arranque el servicio `app`:

1. Ejecuta `composer install` si falta la carpeta `vendor`
2. Copia `.env.example` a `.env` si no existe y genera `APP_KEY`
3. Ejecuta `php artisan migrate --force`

### Variables útiles (`.env` o entorno)

| Variable               | Descripción                    | Por defecto  |
|------------------------|--------------------------------|--------------|
| `APP_PORT`             | Puerto HTTP (nginx → host)      | `8080`       |
| `DB_DATABASE`        | Nombre de la base               | `cms`        |
| `DB_USERNAME` / `DB_PASSWORD` | Credenciales MySQL      | `cms` / `secret` |
| `MYSQL_ROOT_PASSWORD`  | Root de MySQL                   | `rootsecret` |
| `MYSQL_PORT`           | MySQL expuesto al host          | `33060`      |

Las variables `DB_*` del servicio `app` en `docker-compose.yml` sobrescriben los valores de `.env` dentro del contenedor, de modo que la conexión a `mysql` sea correcta sin editar archivos.

### Datos de ejemplo (opcional)

Tras levantar los contenedores:

```bash
docker compose exec app php artisan db:seed
```

Usuarios creados:

- **admin@example.com** / `password` (rol administrador)
- **editor@example.com** / `password` (rol editor)

## Autenticación

1. `POST /api/auth/login` con JSON `{"email":"...","password":"..."}`
2. Respuesta incluye `token` (Bearer). Enviar cabecera `Authorization: Bearer <token>`
3. `POST /api/auth/logout` (revoca el token actual)

Usuarios **inactivos** no pueden iniciar sesión ni crear/editar/eliminar artículos (sí pueden listar y ver si ya tienen un token; en la práctica el login está bloqueado).

## Endpoints principales (prefijo `/api`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/auth/login` | Login (público, throttled) |
| POST | `/auth/logout` | Cerrar sesión |
| GET,POST,… | `/articles` | CRUD artículos (escritura solo usuarios activos) |
| GET,POST,… | `/categories` | CRUD categorías |
| GET,POST,… | `/users` | CRUD usuarios (**solo rol admin**) |

Convenciones REST habituales: `201` al crear, `200` OK, `401` no autenticado, `403` prohibido, `404` no encontrado, `422` error de validación o regla de negocio (p. ej. eliminar categoría con artículos).

## Reglas de negocio destacadas

- **Artículos**: `slug` generado desde el título (único). Al menos una categoría. Solo usuarios **activos** pueden crear, actualizar o eliminar artículos.
- **Categorías**: no se pueden eliminar si tienen artículos asociados.
- **Usuarios**: roles `admin` y `editor`; solo **admin** gestiona usuarios vía API.

## Arquitectura y patrones

- **Repository**: `ArticleRepository` + `ArticleRepositoryInterface` para acceso a datos de artículos.
- **Strategy**: `UniqueSlugFromTitleStrategy` + `SlugGenerationStrategyInterface` para generar slugs únicos a partir del título.

## Tests

Dentro del contenedor (o con PHP 8.2+ y Composer en local):

```bash
docker compose exec app php artisan test
```

Los tests usan SQLite en memoria (`phpunit.xml`).

## Estructura relevante

- `app/Http/Controllers/Api/` — controladores REST
- `app/Repositories/` — repositorio de artículos
- `app/Strategies/` — estrategia de slug
- `app/Enums/` — estados y roles
- `routes/api.php` — rutas API
- `docker-compose.yml`, `docker/` — entorno Docker
