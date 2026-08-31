# Legacy Parent Application Reference (`sjai-v6`)

- **Parent Monolith Path:** `/Users/aldrich/Desktop/workspace/laravel-projects/sjai-v6`
- **Shared Database:** `flutter-student-api` and `sjai-v6` share the same MySQL database schema.
- **Development Workflow:**
  1. When creating, modifying, or extending any student/mobile API module, always inspect the corresponding module in `sjai-v6` first (e.g. `sjai-v6/app/Http/Controllers/Control_Panel_Student/`, `sjai-v6/app/Models/`, `sjai-v6/database/migrations/`).
  2. Adopt the business logic, audience filters, status constants, and database relationships established in `sjai-v6`.
  3. Transform the output into clean, standardized RESTful JSON envelopes using modern Laravel 12 best practices (`FormRequest`, thin `Controller`, `Service` layer, `JsonResource`, and `ResponseApi` trait).
