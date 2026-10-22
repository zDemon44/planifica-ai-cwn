# Planifica AI CWN

Aplicación web para la creación, revisión y aprobación de planificaciones académicas semanales de la Escuela de Educación Básica Carmen Wither Navarro.

## Tecnologías

- Laravel 13 y PHP 8.3.
- MySQL.
- Livewire.
- Vite y Tailwind CSS.

## Instalación local

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

Para iniciar el entorno de desarrollo:

```bash
composer run dev
```

## Sincronización de cambios SQL

Los cambios incrementales que deban compartirse manualmente entre integrantes se guardan en [`sql/`](sql/README.md). Los archivos usan fecha y un número consecutivo para mantener un orden de ejecución claro.
