# Implementation Plan: Student Class Schedule API Module

Build the **Student Class Schedule API** to allow authenticated students to retrieve their scheduled subjects for the current (or specified) school year, matching the mobile UI layout (Day tabs: Mon-Fri, subject cards with time, teacher, room, and subject details).

## User Review Required

> [!IMPORTANT]
> - The API uses `class_details` as the parent relation from the student's `enrollment`, linking to `class_subject_details`, `subject_details`, `faculty_informations`, and `rooms`.
> - Destructive database commands (`migrate:fresh`, `db:wipe`, etc.) will NOT be run against MySQL to safeguard sample/live data. Automated tests will use SQLite in-memory (`:memory:`).

---

## Proposed Changes

### 1. Models & Relationships

#### [NEW] [Room.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/Room.php)
- Create `Room` Eloquent model for the `rooms` table (`room_code`, `room_description`, `status`).

#### [MODIFY] [ClassSubjectDetail.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/ClassSubjectDetail.php)
- Add `room()` relation (`belongsTo(Room::class, 'room_id', 'id')`).
- Add `classDetail()` relation (`belongsTo(ClassDetail::class, 'class_details_id', 'id')`).

#### [MODIFY] [ClassDetail.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/ClassDetail.php)
- Add `room()` relation (`belongsTo(Room::class, 'room_id', 'id')`).

---

### 2. API Layer & Transformations

#### [NEW] [ClassScheduleResource.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Resources/ClassScheduleResource.php)
- Formats class schedule payload with class details (`section`, `grade_level`, `adviser`, `school_year`) and day-by-day scheduled subjects:
  - Day mapping (`Mon`, `Tue`, `Wed`, `Thu`, `Fri`, `Sat`, `Sun`).
  - Formatted time (`time_from`, `time_to`, `time_display` e.g., `07:30 - 08:30`).
  - Subject info (`subject_name`, `subject_code`).
  - Teacher/Faculty (`faculty_name`).
  - Room (`room_code`, `room_description`).

#### [NEW] [ClassScheduleService.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Services/ClassScheduleService.php)
- Business logic using `ResponseApi` and `SchoolYear` traits:
  - Fetches the student's active enrollment for the requested `school_year_id` (or active school year fallback).
  - Retrieves `classDetail` with `classSubjectDetails`, `subjectDetails`, `assignFaculty`, and `room`.
  - Parses and organizes schedules by day for the Flutter UI.

#### [NEW] [ClassScheduleController.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Controllers/ClassScheduleController.php)
- Thin controller delegating `index(Request $request)` directly to `ClassScheduleService`.

#### [MODIFY] [routes/api.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/routes/api.php)
- Register `Route::prefix('class-schedules')->group(...)` under `auth:sanctum` middleware.

---

### 3. Automated Feature Testing (Pest PHP with SQLite)

#### [NEW] [ClassScheduleApiTest.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/tests/Feature/ClassScheduleApiTest.php)
- Test authenticated student retrieval of class schedule.
- Test active school year fallback behavior.
- Test unauthenticated 401 response.
- Runs purely on SQLite in-memory without touching MySQL.

---

## Verification Plan

### Automated Tests
- Run Pest test suite against SQLite:
  ```bash
  php artisan test --filter=ClassScheduleApiTest
  ```

### Manual / API Response Verification
- Validate the JSON structure matching the Flutter design requirements:
  ```json
  {
    "status": true,
    "message": "Class schedule successfully fetched.",
    "data": {
      "section": "St. Jude",
      "grade_level": 10,
      "adviser": "Mr. John Doe",
      "school_year": "2025-2026",
      "schedules_by_day": {
        "Mon": [
          {
            "subject": "Mathematics",
            "subject_code": "MATH10",
            "faculty_name": "Mr. Aljon Ebero",
            "room": "Room 101",
            "time_from": "07:30",
            "time_to": "08:30",
            "time_display": "07:30 - 08:30"
          }
        ]
      }
    }
  }
  ```
