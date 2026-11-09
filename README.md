# Sistema Contable

Aplicación web multiempresa para firmas contables. Cada firma administra a sus clientes y a sus contadores, y cada contador lleva la contabilidad de las empresas que tiene asignadas: catálogo de cuentas, asientos, libros y estados financieros.

Proyecto final del curso de Análisis de Sistemas.

## Funcionalidades

- **Usuarios y roles:** super administrador, administrador de firma y contador. Cada usuario solo accede a los datos de las empresas que tiene asignadas.
- **Catálogo de cuentas** jerárquico por empresa, con tipo activo, pasivo, patrimonio, ingreso, gasto y naturaleza deudora o acreedora.
- **Periodos fiscales** sin traslapes entre sí.
- **Asientos contables** de varias líneas, validados con el principio de partida doble.
- **Libro diario, libro mayor y balance de comprobación**, con filtro por periodo.
- **Estado de resultados** por periodo, con la utilidad o pérdida.
- **Balance general** a una fecha de corte, con subtotales por nivel y validación de la ecuación contable.
- **Cierre de periodo fiscal:** genera el asiento de cierre e impide modificar los asientos del periodo cerrado.

## Tecnologías

- PHP 8.3+ y Laravel 13
- MySQL 8
- Blade, Tailwind CSS 4 y Vite
- PHPUnit para las pruebas

## Instalación

El código de la aplicación está en la carpeta `sistema-contable`.

```bash
cd sistema-contable
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Crea una base de datos vacía en MySQL y configúrala en `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_contable
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

Crea las tablas con los datos de demostración, compila los recursos y levanta el servidor:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

La aplicación queda en http://localhost:8000.

## Usuarios de demostración

El seeder crea estos usuarios, todos con la contraseña `password`:

| Correo | Rol |
| --- | --- |
| superadmin@example.com | Super administrador |
| admin@example.com | Administrador de firma (Contadores Pro SAS) |
| contador1@example.com | Contador (Contadores Pro SAS) |
| contador2@example.com | Contador (Contadores Pro SAS) |
| admin2@example.com | Administrador de firma (Asesores Andinos SA) |
| contador3@example.com | Contador (Asesores Andinos SA) |

Cámbialos antes de publicar la aplicación.

## Pruebas

El proyecto se desarrolló con TDD. Las pruebas usan una base SQLite en memoria, así que no tocan tus datos:

```bash
cd sistema-contable
php artisan test
```

## Documentación

Los diagramas del análisis y diseño están en la carpeta del proyecto en Google Drive.
https://drive.google.com/drive/folders/1BkldnGNwEmP7x2atS2Q7M3rWXeiQPjIr?usp=drive_link
