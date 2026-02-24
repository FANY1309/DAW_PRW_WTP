# WhosThatPokemon (WTP)

Aplicacion web de adivinanza diaria inspirada en Wordle y en "Who's That Pokemon".

## Estado actual del proyecto
- Reto diario con pistas progresivas.
- Login/registro y sesion por usuario.
- Ranking global de usuarios autenticados.
- El rol `admin`:
  - puede jugar sin limite diario;
  - no aparece en el ranking global;
  - puede sincronizar Pokemon por generacion desde PokeAPI;
  - puede crear retos diarios manualmente (fecha + Pokemon), sin permitir fechas repetidas.

## Requisitos
- Sistema operativo: Linux/WSL (probado en Ubuntu dentro de WSL).
- PHP: `>= 8.1` (recomendado `8.3`) con extensiones:
  - `pdo_mysql`
  - `json`
  - `mbstring`
  - `curl`
- MySQL: `>= 8.0` (tambien funciona con MariaDB reciente).
- Servidor web:
  - Opcion A (rapida): servidor embebido de PHP.
  - Opcion B: Apache 2.4 + mod_php.
- Node.js: no requerido.
- Java: no requerido.
- .NET: no requerido.
- Python: no requerido.

## Instalacion y ejecucion
Ejecuta estos comandos desde la raiz del proyecto.

### 1) Instalar dependencias del sistema (Ubuntu/WSL)
```bash
sudo apt update
sudo apt install -y apache2 mysql-server php8.3 libapache2-mod-php8.3 php8.3-mysql php8.3-curl
```

### 2) Crear la base de datos e importar esquema/datos demo
```bash
mysql -uroot -p -e "CREATE DATABASE IF NOT EXISTS wtp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -p wtp < database/crear_tablas.sql
mysql -uroot -p wtp < database/rellenar_tablas.sql
```

### 3) Configurar variables de entorno
```bash
cp .env.example .env
```
Edita `.env` con tus credenciales reales de MySQL.

### 4) Ejecutar aplicacion
Opcion A (recomendada para desarrollo rapido):
```bash
php -S 127.0.0.1:8000 -t public
```
Abrir: `http://127.0.0.1:8000`

Opcion B (Apache):
```bash
sudo systemctl restart mysql
sudo systemctl restart apache2
```
Abrir: `http://localhost/wtp/public`

## Variables de entorno
Archivo de ejemplo actual (`.env.example`):
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=wtp
DB_USER=wtp_user
DB_PASS=password
APP_BASE_URL=/wtp
APP_ENV=development
```

Notas:
- `APP_ENV=production` desactiva el bloque debug del frontend.
- Si ejecutas con `php -S`, normalmente puedes usar `APP_BASE_URL=/`.

## Credenciales de demo
Datos cargados por `database/rellenar_tablas.sql`:

- Usuario normal:
  - usuario: `tester`
  - email: `tester@wtp.local`
  - password: `123456`
  - rol: `usuario`

- Administrador:
  - usuario: `admin`
  - email: `admin@wtp.local`
  - password: `123456`
  - rol: `admin`

## Endpoints principales
- `GET /api/reto/hoy`
- `GET /api/pokemon/lista`
- `POST /api/partida/intento`
- `GET /api/ranking/global`
- `GET /api/auth/me`
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/admin/pokemon/sync-generation` (solo admin)
- `POST /api/admin/reto-diario` (solo admin)
