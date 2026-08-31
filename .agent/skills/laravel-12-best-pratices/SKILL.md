---
name: laravel-12-best-practices
description: Software engineering best practices for Laravel 12.x, covering API module development, Controller-Service architecture, Eloquent, Sanctum authentication, FormRequests, API Resources, ResponseApi trait, testing with Pest, security, and performance. Activates when creating, modifying, or refactoring Laravel APIs, controllers, services, routes, models, or tests.
compatibility: Requires Laravel 12.x and PHP 8.2+
license: Apache-2.0
metadata:
  author: mbuyco
  version: "1.2"
allowed-tools: Read
---

# Laravel 12.x Best Practices & API Architecture

This skill outlines the recommended software engineering practices for developing applications and RESTful APIs with Laravel 12.x. It incorporates modern PHP 8.2+ features, thin controller / service layer architecture, standardized response traits, strict typing, database preservation rules, implementation plan archiving, and industry-standard patterns.

---

## 0. Implementation Plans & Legacy System Architecture

> [!IMPORTANT]
> **1. MANDATORY PLAN ARCHIVING:**
> For every feature or module implementation:
> - Formulate and review the implementation plan.
> - Save the final implementation plan as `<title_of_the_implementation>.md` (e.g. `implemented-plans/student_announcement_api.md`) inside the `implemented-plans/` directory in the project root.
>
> **2. LEGACY PARENT APPLICATION (`sjai-v6`):**
> - The codebase at `../sjai-v6` is the parent application sharing the exact same MySQL database.
> - Always cross-reference `sjai-v6` controllers (`Control_Panel_Student/`), models, and business logic before building mobile API counterparts.

---

## 1. API Architecture & Layer Standards

Every API feature module in this project follows a strict **Layered Architecture Pipeline**:

```
Client Request
      │
      ▼
Routes (routes/api.php) [Sanctum Auth Middleware & Route Prefix Groups]
      │
      ▼
FormRequest (app/Http/Requests/*) [Strict Validation & Authorization]
      │
      ▼
Controller (app/Http/Controllers/*) [Thin Controller - delegates immediately to Service]
      │
      ▼
Service Layer (app/Services/*) [Business Logic, Filtering, Transactions, Models]
      │
      ▼
API Resource / Collection (app/Http/Resources/*) [Data Transformation]
      │
      ▼
ResponseApi Trait (app/Traits/ResponseApi.php) [Standardized JSON Envelope: status, message, data]
```

### Standard Implementation Sample

#### A. Route Definition (`routes/api.php`)
Always group protected endpoints under `auth:sanctum` and use prefixed route groups:
```php
use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::prefix('articles')->group(function () {
        Route::get('/', [ArticleController::class, 'index']);
        Route::get('/{id}', [ArticleController::class, 'show']);
        Route::post('/', [ArticleController::class, 'store']);
        Route::put('/{id}', [ArticleController::class, 'update']);
        Route::delete('/{id}', [ArticleController::class, 'destroy']);
    });
});
```

#### B. Thin Controller (`app/Http/Controllers/ArticleController.php`)
- Controllers must contain **no database queries** or **direct business logic**.
- Inject the Service class in constructor using PHP 8 constructor promotion.
- Return the service method results directly.

```php
namespace App\Http\Controllers;

use App\Http\Requests\ArticleStoreRequest;
use App\Http\Requests\ArticleUpdateRequest;
use App\Services\ArticleService;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(
        protected ArticleService $service
    ) {}

    public function index(Request $request)
    {
        return $this->service->index($request);
    }

    public function show(int $id)
    {
        return $this->service->show($id);
    }

    public function store(ArticleStoreRequest $request)
    {
        return $this->service->store($request->validated());
    }

    public function update(ArticleUpdateRequest $request, int $id)
    {
        return $this->service->update($request->validated(), $id);
    }

    public function destroy(int $id)
    {
        return $this->service->destroy($id);
    }
}
```

#### C. Service Layer (`app/Services/ArticleService.php`)
- Uses `ResponseApi` (and optional `SchoolYear`) traits.
- Handles model filtering, database queries, transactions, and pagination.
- Returns standardized responses via `$this->success()` and `$this->error()`.

```php
namespace App\Services;

use App\Http\Resources\ArticleListsResource;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Traits\ResponseApi;
use App\Traits\SchoolYear;
use Exception;
use Illuminate\Http\Response;

class ArticleService
{
    use ResponseApi, SchoolYear;

    public function __construct(
        protected Article $model
    ) {}

    public function index($request)
    {
        $limit = $request->limit ?? 10;
        $data = $this->model
            ->filter($request)
            ->paginate($limit);

        return $this->success(
            'Data successfully listed.',
            Response::HTTP_OK,
            new ArticleListsResource($data)
        );
    }

    public function show(int $id)
    {
        try {
            $data = $this->model->find($id);

            if (! $data) {
                return $this->error('Article not found.', Response::HTTP_NOT_FOUND);
            }

            return $this->success(
                'Data successfully fetched.',
                Response::HTTP_OK,
                new ArticleResource($data)
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function store(array $validatedData)
    {
        try {
            $created = $this->model->create($validatedData);

            return $this->success(
                'Data successfully created.',
                Response::HTTP_CREATED,
                new ArticleResource($created)
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function update(array $validatedData, int $id)
    {
        try {
            $item = $this->model->find($id);
            if (! $item) {
                return $this->error('Article not found.', Response::HTTP_NOT_FOUND);
            }

            $item->update($validatedData);

            return $this->success(
                'Data successfully updated.',
                Response::HTTP_OK,
                new ArticleResource($item)
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function destroy(int $id)
    {
        try {
            $item = $this->model->find($id);
            if (! $item) {
                return $this->error('Article not found.', Response::HTTP_NOT_FOUND);
            }

            $item->delete();

            return $this->success('Data successfully deleted.', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
```

#### D. Form Request Validation (`app/Http/Requests/ArticleStoreRequest.php`)
- Put all input validation in dedicated `FormRequest` classes.
- Use explicit validation array rules.

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ];
    }
}
```

#### E. API Resource (`app/Http/Resources/ArticleResource.php`)
```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'content'    => $this->content,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

---

## 2. Project Setup & Architecture

### Starter Kits
For new projects, prefer using official Laravel 12 starter kits with modern defaults:
- **Frontend:** React, Vue, or Livewire starter kits integrating **Inertia 2.0**, **TypeScript**, **Shadcn/UI**, and **Tailwind CSS**.
- **Authentication:** **WorkOS AuthKit** or **Laravel Sanctum** for SPA / Mobile APIs.

### Directory Structure & Strict Typing
- **Standard Structure:** Keep logic organized across `app/Http/Controllers`, `app/Http/Requests`, `app/Http/Resources`, `app/Services`, and `app/Models`.
- **Strict Typing:** Enable strict types in all PHP files (`declare(strict_types=1);`).

---

## 3. Code Quality & Style

- **Linting:** Use **Laravel Pint** (built on top of PHP-CS-Fixer) to enforce the PER / Laravel coding style (`./vendor/bin/pint`).
- **Static Analysis:** Maintain safety with **PHPStan** (Level 5 minimum recommended).
- **Refactoring:** Utilize `rector/rector` to automatically leverage modern PHP 8.2+ syntax (constructor promotion, match expressions, typed properties).

---

## 4. Eloquent & Database Safety Rules

> [!CAUTION]
> **DATABASE PRESERVATION POLICY:**
> - **DO NOT** run `php artisan migrate:fresh`, `php artisan migrate:refresh`, `php artisan migrate:reset`, or `php artisan db:wipe` on the MySQL database.
> - The MySQL database contains live sample and testing records that the user inspects.
> - Only run incremental, non-destructive migrations: `php artisan migrate`.
> - For automated testing, sandboxing, or test data generation, **ALWAYS use SQLite** (`DB_CONNECTION=sqlite` with `:memory:` or temporary SQLite files).

### Model Practices
- **UUIDs:** Leverage Laravel 12's default support for **UUIDv7** in the `HasUuids` trait for time-sortable unique identifiers when required.
- **Mass Assignment:** Always specify `$fillable` or `$guarded` properties.
- **Strictness:** Prevent lazy loading in development to avoid N+1 query bugs:
  ```php
  // In AppServiceProvider::boot()
  Model::preventLazyLoading(! $this->app->isProduction());
  ```

### Data Handling
- **Collections:** Use strict collection methods like `Arr::sole()` when expecting a single item.
- **Context Facade:** Use `Context` for request-scoped tracing or metadata across logs and events.

---

## 5. Testing with Pest PHP & SQLite

- **Framework:** Prefer **Pest PHP** for clean, readable API feature tests.
- **SQLite Database:** Automated tests run against SQLite in-memory (`:memory:`) as configured in `phpunit.xml`. This ensures the MySQL development database is never wiped or touched by tests.
- **Sanctum Authentication:** Use `Sanctum::actingAs($user)` in tests.
- **Model Factories:** Always use model factories for test data generation.
- **JSON Assertions:** Use fluent JSON assertions for validating API envelopes.

```php
use App\Models\Article;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('authenticated user can retrieve article list', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Article::factory()->count(3)->create();

    $response = $this->getJson('/api/articles');

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'data' => [
                    '*' => ['id', 'title', 'content']
                ]
            ]
        ]);
});
```

---

## 6. Security

- **Validation:** Always validate request payloads via `FormRequest` classes.
- **Authorization:** Use Policies and Gates for entity access control (`$this->authorize('update', $model)`).
- **Sanitization & Escaping:** Use Eloquent parameter binding; never concatenate user input into raw SQL queries.

---

## 7. Performance

- **Caching:** Cache expensive queries using `Cache::remember()`.
- **Eager Loading:** Always eager load relationships (`with(['relation'])`) to eliminate N+1 issues.
- **Queues:** Dispatch background jobs for notifications, exports, or third-party webhooks.
