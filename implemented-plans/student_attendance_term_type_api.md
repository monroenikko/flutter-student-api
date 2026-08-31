# Implementation Plan: Student Attendance Merging for New Term Type

Support merging `attendance_first`, `attendance_second`, and `attendance_third` from `enrollments` when `term_type = 'new'` across both Junior High and Senior High in `/api/class-details`, while preserving legacy `'old'` term behavior.

## Summary of Changes

1. **Models**:
   - [`Enrollment`](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/Enrollment.php): Added `attendance`, `attendance_first`, `attendance_second`, `attendance_third` to `$fillable`.
   - [`StudentAttendance`](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/StudentAttendance.php): Added `senior3_months_header` to `$fillable`.

2. **Controller & Resource**:
   - [`ClassDetailController`](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Controllers/ClassDetailController.php): Decodes `senior3_months_header` as `table_header3` and passes `attendance_header` into request.
   - [`ClassDetailResource`](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Resources/ClassDetailResource.php):
     - Checks `term_type` from `class_details.term_type`.
     - When `term_type === 'new'`: Merges `attendance_first`, `attendance_second`, and `attendance_third` fitted against `senior1_months_header`, `senior2_months_header`, and `senior3_months_header` for both Junior and Senior grade levels.
     - When `term_type === 'old'`: Preserves legacy separate `attendance_junior`, `attendance_senior1`, and `attendance_senior2`.

3. **Automated Testing**:
   - [`ClassDetailApiTest`](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/tests/Feature/ClassDetailApiTest.php): 4 test cases covering unauthenticated access, new term Junior High, new term Senior High, and old term legacy structure.
