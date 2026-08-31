# Implementation Plan: Student GradeSheet term_type Support (New vs Old) with Sub-Subjects & Electives

Support `term_type = 'new'` (3 terms: `fir_g`, `sec_g`, `thi_g` without `fou_g`) in the student GradeSheet API while preserving existing `term_type = 'old'` functionality (4 quarters for Junior High, 2 semesters for Senior High), including sub-subject title resolution with indentation flags and senior-level elective subjects.

## User Review Required

> [!IMPORTANT]
> - `term_type` is retrieved directly from `class_details.term_type` (defaulting to `'old'` if null).
> - **Sub-Subjects**:
>   - When enrolled with a sub-subject (`sub_subject_id`), the sub-subject title and code are loaded from `sub_subject_details`.
>   - Formatted title casing is applied to subject titles.
>   - An `is_sub_subject: true` boolean is provided for UI indention / italicization.
> - **Senior Elective Subjects**:
>   - Detected via `subject_categories.code = 'elective'`.
>   - An `is_elective: true` flag and `term` property indicate which term the elective was taken.
>   - For `term_type = 'new'`, elective `final_g` evaluates only the specific term taken.
> - For `term_type = 'old'`:
>   - All existing behavior is retained (Junior High: 4 quarters including `fou_g`; Senior High: `first_sem` & `second_sem`).
> - Destructive database commands were avoided. Automated tests run purely against SQLite in-memory (`:memory:`).

---

## Implemented Changes

### 1. Models & Relations

#### [MODIFY] [ClassDetail.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/ClassDetail.php)
- Added `term_type` to `$fillable`.

#### [NEW] [SubSubjectDetail.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/SubSubjectDetail.php)
- Created model for `sub_subject_details` table with parent `SubjectDetail` relationship.

#### [NEW] [SubjectCategory.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/SubjectCategory.php)
- Created model for `subject_categories` table (`code`, `description`).

#### [MODIFY] [SubjectDetail.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/SubjectDetail.php)
- Added `subSubjects()` and `subjectCategory()` relations.

#### [MODIFY] [StudentEnrolledSubject.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/StudentEnrolledSubject.php)
- Added `subSubject()` relation to `SubSubjectDetail`.

#### [MODIFY] [ClassSubjectDetail.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Models/ClassSubjectDetail.php)
- Added `subjectCategory()` relation.

---

### 2. Service Layer

#### [MODIFY] [ClassRecordService.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Services/ClassRecordService.php)
- Included `term_type` in `classDetail` select columns.
- Eager-loaded `sub_subject_id`, `subSubject`, and `subjectCategory` relationships.

#### [MODIFY] [GradeSheetService.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Services/GradeSheetService.php)
- Reads `term_type` from `$class_detail->classDetail->term_type`.
- If `term_type === 'new'`, delegates to `GradeSheetResource($class_detail)` for the 3-term layout.
- If `term_type === 'old'` (or default), preserves existing logic:
  - Grade level >= 11: `SeniorGradeSheetResource` for `first_sem` and `second_sem`.
  - Grade level <= 10: `GradeSheetResource` with 4 quarters.

---

### 3. API Resources

#### [MODIFY] [GradeSheetResource.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Resources/GradeSheetResource.php)
- Added `term_type` at top level.
- Added sub-subject resolution, title-casing formatter, and `is_sub_subject` indention flag.
- Added `is_elective` and `term` properties for elective subjects.
- For `term_type = 'new'`: returns `fir_g`, `sec_g`, `thi_g` (excludes `fou_g`).
- Calculates `final_g` based on whether the subject is elective (single term) or regular (average of 3 terms).

#### [MODIFY] [SeniorGradeSheetResource.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/app/Http/Resources/SeniorGradeSheetResource.php)
- Added sub-subject name resolution, `is_sub_subject`, `is_elective`, and null-safe property accessors.

---

### 4. Automated Tests

#### [NEW] [GradeSheetApiTest.php](file:///Users/aldrich/Desktop/workspace/laravel-projects/flutter-student-api/tests/Feature/GradeSheetApiTest.php)
- Tests `term_type = 'new'` returning 3 terms (`fir_g`, `sec_g`, `thi_g`) without `fou_g` and correct 3-term average `final_g`.
- Tests sub-subject title display and `is_sub_subject: true` indention flag.
- Tests senior-level elective subjects in `term_type = 'new'` with term-specific grade calculation.
- Tests `term_type = 'old'` (Junior High) returning 4 quarters (`fir_g`, `sec_g`, `thi_g`, `fou_g`) and 4-quarter average `final_g`.
- Tests `term_type = 'old'` (Senior High) returning `first_sem` and `second_sem`.
- Tests unauthenticated request returning 401.

---

## Verification Results

- All 33 tests across the entire test suite passed successfully.
```bash
php artisan test
```
