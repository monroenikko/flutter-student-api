# Payment Registration Finance Notification & OneSignal Push Integration

## Overview
When a student or parent submits their payment receipt in the mobile payment registration module (`POST /api/payment-registration`), the backend automatically:
1. Saves a database notification in the `notifications` table for all active Finance users (`role = 6`, `status = 1`).
2. Triggers a OneSignal push notification broadcast to the OneSignal `player_id`s associated with those Finance users.

This matches the legacy architecture in `sjai-v6` while ensuring robust error handling, background resilience, and high test coverage.

---

## Architectural Pipeline
```
Student / Parent (Mobile App)
      │
      ▼
POST /api/payment-registration (PaymentRegistionController@store)
      │
      ▼
StoreRequest Validation (app/Http/Requests/PaymentRegistration/StoreRequest.php)
      │
      ▼
PaymentService::store (app/Services/PaymentService.php)
      │
      ├─► Upload Receipt Image (public/img/receipt/)
      ├─► Save / Update Transaction & TransactionMonthlyPayment
      ├─► Save TransactionOtherFee & TransactionDiscount
      ├─► DB::commit()
      │
      ▼
PaymentNotificationHelper::notifyFinanceOnPaymentRegistration
      │
      ├─► 1. Query Active Finance Users (User::where('role', 6)->where('status', 1))
      ├─► 2. Save Database Notification ($financeUser->notify(new StudentPaymentRegisteredNotification(...)))
      │      └─► Saved to `notifications` table with type `student_payment_registration`
      └─► 3. Send OneSignal Push Notification (OneSignalService::sendPush(...))
             └─► Fetches player_ids from `subscriptions` table for finance user IDs
             └─► Dispatches push message with formatted amount and student name
```

---

## Files Created / Modified
- `config/one-signal.php`: Configuration settings for OneSignal API.
- `app/Services/OneSignalService.php`: REST API integration for OneSignal push notifications.
- `app/Notifications/StudentPaymentRegisteredNotification.php`: Laravel database notification class.
- `app/Helpers/PaymentNotificationHelper.php`: Dispatches notifications to finance users and pushes via OneSignal.
- `app/Services/PaymentService.php`: Calls notification helper after successful transaction commit.
- `tests/Feature/PaymentRegistrationApiTest.php`: Feature test suite validating database notifications, OneSignal dispatch, role filtering, and graceful error handling.
