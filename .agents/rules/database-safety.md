# Database Safety & Preservation Rules

- **NEVER** run destructive database commands against the MySQL database:
  - ❌ `php artisan migrate:fresh`
  - ❌ `php artisan migrate:refresh`
  - ❌ `php artisan migrate:reset`
  - ❌ `php artisan db:wipe`
- The development MySQL database contains live sample data used for manual testing and data inspection.
- Only run incremental, non-destructive migrations: `php artisan migrate`.
- All automated tests, factory seeding tests, and sandboxed checks must use **SQLite** (e.g. `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).
