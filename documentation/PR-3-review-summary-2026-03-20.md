# PR 3 Review Summary

תאריך סקירה: 2026-03-20

PR: `#3`

כותרת: `Laravel Fixer`

Branch: `shift-169390`

URL: `https://github.com/nm-digitalhub/MVP-RSVP-KALFA.me/pull/3`

Diff summary: `105 files changed, 714 insertions(+), 539 deletions(-)`

## How This Review Was Done

- קריאת ה-diff המלא של `main...pr-3-review`
- מעבר על הקבצים בעלי הסיכון הגבוה: `auth`, `billing`, `controllers`, `middleware`, `models`, `routes`, `views`, `vendor/composer`
- אימות boot של snapshot זמני של ה-PR תחת `/tmp/pr3-review`
- אימות הרצה ישיר של כל ה-`FormRequest` החדשים

## Findings

### 1. Critical: password reset validation is broken at runtime

קובץ: `app/Http/Requests/Auth/UpdatePasswordRequest.php`

מיקום: שורה `17`

הבעיה:

- הקובץ משתמש ב-`Rules\Password::defaults()` בלי `use Illuminate\Validation\Rules;`
- בפועל זה נפתר ל-`App\Http\Requests\Auth\Rules\Password`, מחלקה שלא קיימת
- משמעות: מסלול עדכון הסיסמה ייפול ב-runtime ברגע ש-`rules()` יופעל

אימות שבוצע:

- הרצה ישירה של `UpdatePasswordRequest->rules()` על snapshot של ה-PR החזירה:
- `Class "App\Http\Requests\Auth\Rules\Password" not found`

## 2. Medium: there are no tests in a very wide behavioral PR

הבעיה:

- ה-PR משנה `controllers`, `FormRequests`, `auth`, `billing`, `middleware`, `views`, `routes`, `models` ו-`vendor`
- אין שום שינוי תחת `tests/`
- בפרויקט הזה יש דרישה מפורשת שכל שינוי יהיה מכוסה בתוכניתית, ובמיוחד שינויים ב-auth ו-payment

השפעה:

- אין כיסוי לרגרסיות בזרמי `password reset`, `checkout`, `coupon validation`, `organization settings`, `seat assignments`, `invitations`

## 3. Medium: Composer platform guard was removed from committed vendor files

קבצים:

- `vendor/composer/autoload_real.php`
- `vendor/composer/platform_check.php`

הבעיה:

- ה-`require` של `platform_check.php` הוסר מ-`autoload_real.php`
- `platform_check.php` עצמו נמחק
- בנוסף נכנסו ל-PR קבצי `vendor/composer/*` גנרטיביים שתלויים בסביבת ריצה

השפעה:

- נעלמת בדיקת runtime המוקדמת של Composer להתאמת פלטפורמה
- זה גם מוסיף רעש generated לקוד reviewable ומקשה להבחין בשינויים אפליקטיביים אמיתיים

## Change Summary

### Controllers

- `app/Http/Controllers/Api/*`: ברוב ה-API controllers הוחלף `$this->authorize()` ב-`Gate::authorize()`
- חלק מה-controllers עברו מ-inline validation ל-`FormRequest` ייעודיים
- `app/Http/Controllers/Auth/*`: הוולידציה הוצאה ל-`FormRequest`, ונעשה מעבר עקבי יותר ל-`$request->user()` ו-`$request->session()`
- `app/Http/Controllers/Dashboard/OrganizationSettingsController.php`: וולידציה הוצאה ל-`UpdateOrganizationSettingRequest`
- `app/Http/Controllers/System/AccountPaymentMethodController.php`: נוספה הזרקת `Request` ל-methods שעושים audit log
- `app/Http/Controllers/OrganizationSwitchController.php`: שימוש ב-`$request->user()` במקום `auth()->user()`

### New Form Requests

- נוספו בקשות חדשות תחת `app/Http/Requests/Api/`
- נוספו בקשות חדשות תחת `app/Http/Requests/Auth/`
- נוספה בקשה חדשה תחת `app/Http/Requests/Dashboard/`
- רוב הקבצים רק מעבירים את ה-rules שהיו inline בתוך controllers

### Models

- כמה מודלים עברו ל-`#[Scope]` עם methods מוגנים במקום `scopeXxx()` פומביים
- `AccountProduct` עבר לרישום observer דרך `#[ObservedBy(...)]`
- הוסרו כמה `protected $table` מיותרים ממודלים שבהם שם הטבלה כבר תואם ל-convention

### Services, Seeders, Migration

- רוב השינויים כאן הם style או formatting
- לא נראה שינוי לוגי מהותי ב-`BillingService`, `StubPaymentGateway`, `SumitPaymentGateway`, seeders או migration שנגעו בהם

### Views

- הרבה תבניות Blade עברו ל-directives מודרניים יותר:
- `@if(session(...))` הוחלף ב-`@session(...)`
- `@if(auth()->check())` הוחלף ב-`@auth`
- `@if(!auth()->check())` הוחלף ב-`@guest`
- `@if(isset(...))` הוחלף ב-`@isset`
- בחלק מהקבצים הומרו בלוקים של PHP גולמי ל-`@php ... @endphp`

### Routes

- ב-`routes/web.php` ה-route של `/mobile` הומר מ-closure ל-`Route::view(...)`

### Vendor / Composer

- נכנסו שינויים ב-`vendor/composer/autoload_real.php`
- נכנסו שינויים ב-`vendor/composer/autoload_static.php`
- נכנסו שינויים ב-`vendor/composer/installed.php`
- `vendor/composer/platform_check.php` נמחק

### Test Coverage

- לא נוספו או עודכנו קבצי בדיקות

## Full Changed Files

### Controllers

- `app/Http/Controllers/Api/CheckoutController.php`
- `app/Http/Controllers/Api/CouponValidationController.php`
- `app/Http/Controllers/Api/EventController.php`
- `app/Http/Controllers/Api/EventTableController.php`
- `app/Http/Controllers/Api/GuestController.php`
- `app/Http/Controllers/Api/InvitationController.php`
- `app/Http/Controllers/Api/OrganizationController.php`
- `app/Http/Controllers/Api/PaymentController.php`
- `app/Http/Controllers/Api/SeatAssignmentController.php`
- `app/Http/Controllers/Api/SubscriptionPurchaseController.php`
- `app/Http/Controllers/Auth/ConfirmPasswordController.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/Auth/LogoutController.php`
- `app/Http/Controllers/Auth/PasswordController.php`
- `app/Http/Controllers/Auth/VerificationController.php`
- `app/Http/Controllers/BillingSubscriptionCheckoutController.php`
- `app/Http/Controllers/CheckoutStatusController.php`
- `app/Http/Controllers/CheckoutTokenizeController.php`
- `app/Http/Controllers/Controller.php`
- `app/Http/Controllers/Dashboard/EventController.php`
- `app/Http/Controllers/Dashboard/EventGuestsController.php`
- `app/Http/Controllers/Dashboard/EventInvitationsController.php`
- `app/Http/Controllers/Dashboard/EventSeatAssignmentsController.php`
- `app/Http/Controllers/Dashboard/EventTablesController.php`
- `app/Http/Controllers/Dashboard/OrganizationSettingsController.php`
- `app/Http/Controllers/OrganizationSwitchController.php`
- `app/Http/Controllers/System/AccountPaymentMethodController.php`
- `app/Http/Controllers/System/SystemImpersonationController.php`
- `app/Http/Controllers/System/SystemImpersonationExitController.php`
- `app/Http/Controllers/Twilio/RsvpVoiceController.php`
- `app/Http/Controllers/TwilioController.php`

### Middleware

- `app/Http/Middleware/EnsureAccountActive.php`
- `app/Http/Middleware/EnsureFeatureAccess.php`
- `app/Http/Middleware/EnsureSystemAdmin.php`
- `app/Http/Middleware/ImpersonationExpiry.php`

### Requests

- `app/Http/Requests/Api/CouponValidationRequest.php`
- `app/Http/Requests/Api/StoreEventTableRequest.php`
- `app/Http/Requests/Api/StoreInvitationRequest.php`
- `app/Http/Requests/Api/UpdateEventTableRequest.php`
- `app/Http/Requests/Api/UpdateGuestRequest.php`
- `app/Http/Requests/Api/UpdateOrganizationRequest.php`
- `app/Http/Requests/Api/UpdateSeatAssignmentRequest.php`
- `app/Http/Requests/Auth/SendResetLinkPasswordRequest.php`
- `app/Http/Requests/Auth/StoreConfirmPasswordRequest.php`
- `app/Http/Requests/Auth/UpdatePasswordRequest.php`
- `app/Http/Requests/Dashboard/UpdateOrganizationSettingRequest.php`

### Livewire / Models / Providers / Services

- `app/Livewire/System/Products/ProductTree.php`
- `app/Models/AccountProduct.php`
- `app/Models/AccountSubscription.php`
- `app/Models/BillingWebhookEvent.php`
- `app/Models/Coupon.php`
- `app/Models/EventTable.php`
- `app/Models/Product.php`
- `app/Models/ProductEntitlement.php`
- `app/Models/ProductFeature.php`
- `app/Models/ProductLimit.php`
- `app/Models/ProductPlan.php`
- `app/Models/ProductPrice.php`
- `app/Models/RsvpResponse.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/BillingService.php`
- `app/Services/StubPaymentGateway.php`
- `app/Services/Sumit/EventBillingPayable.php`
- `app/Services/SumitPaymentGateway.php`

### Database

- `database/migrations/2022_12_14_083707_create_settings_table.php`
- `database/seeders/CheckoutWorkflowSeeder.php`
- `database/seeders/InitialAdminSeeder.php`

### Views

- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/billing/subscription-checkout.blade.php`
- `resources/views/components/tree/tree.blade.php`
- `resources/views/components/tree/⚡tree-toolbar.blade.php`
- `resources/views/dashboard/events/show.blade.php`
- `resources/views/dashboard/index.blade.php`
- `resources/views/errors/429-payment.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/livewire/accept-invitation.blade.php`
- `resources/views/livewire/billing/plan-selection.blade.php`
- `resources/views/livewire/dashboard/organization-members.blade.php`
- `resources/views/livewire/organizations/index.blade.php`
- `resources/views/livewire/system/accounts/show.blade.php`
- `resources/views/livewire/system/coupons/edit-coupon.blade.php`
- `resources/views/livewire/system/organizations/show.blade.php`
- `resources/views/livewire/system/products/create-product-wizard.blade.php`
- `resources/views/livewire/system/products/index.blade.php`
- `resources/views/livewire/system/products/show.blade.php`
- `resources/views/livewire/system/settings/index.blade.php`
- `resources/views/livewire/system/users/show.blade.php`
- `resources/views/livewire/tree-branch.blade.php`
- `resources/views/livewire/tree-node.blade.php`
- `resources/views/pages/billing/account.blade.php`
- `resources/views/rsvp/show.blade.php`
- `resources/views/vendor/livewire/bootstrap.blade.php`
- `resources/views/vendor/livewire/simple-bootstrap.blade.php`
- `resources/views/vendor/pagination/bootstrap-4.blade.php`
- `resources/views/vendor/pagination/bootstrap-5.blade.php`
- `resources/views/vendor/pagination/default.blade.php`
- `resources/views/vendor/pagination/semantic-ui.blade.php`
- `resources/views/vendor/pagination/simple-bootstrap-4.blade.php`
- `resources/views/vendor/pagination/simple-default.blade.php`

### Routes

- `routes/web.php`

### Vendor

- `vendor/composer/autoload_real.php`
- `vendor/composer/autoload_static.php`
- `vendor/composer/installed.php`
- `vendor/composer/platform_check.php`

## Bottom Line

- זה PR רחב מאוד של refactor/cleanup אוטומטי
- רוב השינויים הם סגנון, המרה ל-`Gate`, המרה ל-`FormRequest`, ו-modernization של Blade
- כרגע יש לפחות כשל runtime אחד מאומת ב-`UpdatePasswordRequest`
- בנוסף חסר כיסוי בדיקות לחלוטין, ויש גם churn בעייתי תחת `vendor/composer`
