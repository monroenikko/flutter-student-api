# Implementation Plan: Student Announcement API Module

Implement the **Student Announcements API** by porting and standardizing the announcement functionality from the legacy parent application (`sjai-v6`), providing mobile-friendly endpoints for listing announcements, filtering by read/unread/archived tabs, searching, viewing details, and updating read status.

## User Review Required

> [!IMPORTANT]
> - Shares the exact MySQL schema with `sjai-v6` (`announcements` and `announcement_user_states` tables).
> - Audience segmentation matches the student's enrolled grade level: Junior High (Grade <= 10 -> Audience 1 & 3), Senior High (Grade > 10 -> Audience 2 & 3).
> - Destructive migrations (`migrate:fresh`, `db:wipe`) will NOT be run on MySQL. All tests run on SQLite in-memory.

---

## Proposed Changes

### 1. Legacy Parent Application Reference Rule

#### [NEW] [.agent/rules/legacy-parent-reference.md](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/.agent/rules/legacy-parent-reference.md)
- Documents `sjai-v6` (`/Users/aldrich/Desktop/workspace/laravel-projects/sjai-v6`) as the legacy parent monolith that shares the same database.
- Instructs the agent to inspect `sjai-v6` controllers, models, and migrations when developing new mobile API modules.

#### [MODIFY] [laravel-12-best-practices](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/.agent/skills/laravel-12-best-pratices/SKILL.md)
- Add reference to `sjai-v6` under the architectural guidelines.

---

### 2. Models & Data Structures

#### [NEW] [Announcement.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/Announcement.php)
- Eloquent model for `announcements` table with audience/status constants, scopes (`scopeFilter`, `scopePublished`, `scopeForStudentLevel`).

#### [NEW] [AnnouncementUserState.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/AnnouncementUserState.php)
- Eloquent model for `announcement_user_states` table tracking per-user read/unread/archived state (1 = Unread, 2 = Read, 3 = Archived).

---

### 3. API Layer & Service

#### [NEW] [AnnouncementResource.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Resources/AnnouncementResource.php)
- API Resource formatting single announcement item with user state, formatted date, audience label, and status badge colors.

#### [NEW] [AnnouncementService.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Services/AnnouncementService.php)
- Service handling:
  - `resolveStudentLevel()`: Determines if student is Junior (1) or Senior (2) based on active enrollment.
  - `list(Request $request)`: Filter by status tab (`all`, `unread`, `read`, `archived`), search query, sorting, pagination, and status counts.
  - `show(int $id)`: Fetches announcement and automatically marks it as read for the student.
  - `changeStatus(int $id, int $status)`: Updates state to Read (2), Unread (1), or Archived (3).
  - `markAllRead()`: Batch marks all visible published announcements as read.

#### [NEW] [AnnouncementController.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Controllers/AnnouncementController.php)
- Thin controller exposing `index`, `show`, `markRead`, `markUnread`, `markArchived`, and `markAllRead`.

#### [MODIFY] [routes/api.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/routes/api.php)
- Register `Route::prefix('announcements')->group(...)` under `auth:sanctum`.

---

### 4. Automated Tests (Pest PHP with SQLite)

#### [NEW] [AnnouncementApiTest.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/tests/Feature/AnnouncementApiTest.php)
- Test listing announcements segmented by audience.
- Test tab filters (`all`, `unread`, `read`, `archived`) and status counts.
- Test marking single announcement as read/unread/archived.
- Test marking all announcements as read.
- Test unauthenticated 401 response.

---

## Verification Plan

### Automated Tests
- Run test suite on SQLite:
  ```bash
  php artisan test --filter=AnnouncementApiTest
  ```
- Run full test suite:
  ```bash
  php artisan test
  ```
