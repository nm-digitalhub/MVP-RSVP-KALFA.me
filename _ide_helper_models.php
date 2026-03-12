<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * Account layer for entitlements and optional SUMIT customer mapping.
 *
 * type: organization | individual. No enforcement or gating in this phase.
 *
 * @property int $id
 * @property string $type
 * @property int|null $owner_user_id
 * @property int|null $sumit_customer_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $name
 * @property string|null $twilio_subaccount_sid
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountProduct> $accountProducts
 * @property-read int|null $account_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountProduct> $activeAccountProducts
 * @property-read int|null $active_account_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountSubscription> $activeSubscriptions
 * @property-read int|null $active_subscriptions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BillingIntent> $billingIntents
 * @property-read int|null $billing_intents_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountEntitlement> $entitlements
 * @property-read int|null $entitlements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventBilling> $eventsBilling
 * @property-read int|null $events_billing_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountFeatureUsage> $featureUsage
 * @property-read int|null $feature_usage_count
 * @property-read string|null $company
 * @property-read string|null $email
 * @property-read string|null $phone
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Organization> $organizations
 * @property-read int|null $organizations_count
 * @property-read \App\Models\User|null $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \OfficeGuy\LaravelSumitGateway\Models\OfficeGuyToken> $paymentMethods
 * @property-read int|null $payment_methods_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountSubscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereOwnerUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereSumitCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereTwilioSubaccountSid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Account whereUpdatedAt($value)
 */
	class Account extends \Eloquent implements \OfficeGuy\LaravelSumitGateway\Contracts\HasSumitCustomer {}
}

namespace App\Models{
/**
 * Grant: account has a feature_key (from product or manual). No enforcement in this phase.
 *
 * @property int $id
 * @property int $account_id
 * @property string $feature_key
 * @property string|null $value
 * @property int|null $product_entitlement_id
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Enums\EntitlementType|null $type
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\ProductEntitlement|null $productEntitlement
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereFeatureKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereProductEntitlementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountEntitlement whereValue($value)
 */
	class AccountEntitlement extends \Eloquent {}
}

namespace App\Models{
/**
 * Usage tracking per account per feature_key per period. No enforcement in this phase.
 *
 * @property int $id
 * @property int $account_id
 * @property string $feature_key
 * @property int $period_key
 * @property int $usage_count
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereFeatureKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage wherePeriodKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountFeatureUsage whereUsageCount($value)
 */
	class AccountFeatureUsage extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $account_id
 * @property int $product_id
 * @property \App\Enums\AccountProductStatus $status
 * @property \Illuminate\Support\Carbon|null $granted_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property int|null $granted_by
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\User|null $grantedBy
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct whereGrantedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct whereGrantedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountProduct whereUpdatedAt($value)
 */
	class AccountProduct extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $account_id
 * @property int $product_plan_id
 * @property \App\Enums\AccountSubscriptionStatus $status
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\ProductPlan $productPlan
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription whereProductPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription whereTrialEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountSubscription whereUpdatedAt($value)
 */
	class AccountSubscription extends \Eloquent {}
}

namespace App\Models{
/**
 * Purchase abstraction: links account to a future payment/checkout. No enforcement in this phase.
 *
 * @property int $id
 * @property int $account_id
 * @property string $status
 * @property string|null $intent_type
 * @property string|null $payable_type
 * @property int|null $payable_id
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Account $account
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $payable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereIntentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent wherePayableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent wherePayableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingIntent whereUpdatedAt($value)
 */
	class BillingIntent extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $source
 * @property string|null $event_type
 * @property array<array-key, mixed>|null $payload
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BillingWebhookEvent whereUpdatedAt($value)
 */
	class BillingWebhookEvent extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $event_date
 * @property string|null $venue_name
 * @property array<array-key, mixed>|null $settings
 * @property \App\Enums\EventStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\EventBilling|null $eventBilling
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventTable> $eventTables
 * @property-read int|null $event_tables_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Guest> $guests
 * @property-read int|null $guests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invitation> $invitations
 * @property-read int|null $invitations_count
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SeatAssignment> $seatAssignments
 * @property-read int|null $seat_assignments_count
 * @method static \Database\Factories\EventFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereEventDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event whereVenueName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Event withoutTrashed()
 */
	class Event extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $organization_id
 * @property int $event_id
 * @property int|null $plan_id
 * @property int $amount_cents
 * @property string $currency
 * @property \App\Enums\EventBillingStatus $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $account_id
 * @property-read \App\Models\Account|null $account
 * @property-read \App\Models\Event $event
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\Plan|null $plan
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereAmountCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventBilling whereUpdatedAt($value)
 */
	class EventBilling extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property int $capacity
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SeatAssignment> $seatAssignments
 * @property-read int|null $seat_assignments_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventTable withoutTrashed()
 */
	class EventTable extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $group_name
 * @property string|null $notes
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Event $event
 * @property-read \App\Models\Invitation|null $invitation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RsvpResponse> $rsvpResponses
 * @property-read int|null $rsvp_responses_count
 * @property-read \App\Models\SeatAssignment|null $seatAssignment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereGroupName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guest withoutTrashed()
 */
	class Guest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $event_id
 * @property int|null $guest_id
 * @property string $token
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \App\Enums\InvitationStatus $status
 * @property \Illuminate\Support\Carbon|null $responded_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \App\Models\Guest|null $guest
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RsvpResponse> $rsvpResponses
 * @property-read int|null $rsvp_responses_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereGuestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invitation whereUpdatedAt($value)
 */
	class Invitation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $billing_email
 * @property array<array-key, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_suspended
 * @property int|null $account_id
 * @property-read \App\Models\Account|null $account
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventBilling> $eventsBilling
 * @property-read int|null $events_billing_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OrganizationInvitation> $invitations
 * @property-read int|null $invitations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\OrganizationUser|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\OrganizationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereBillingEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereIsSuspended($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Organization whereUpdatedAt($value)
 */
	class Organization extends \Eloquent implements \OfficeGuy\LaravelSumitGateway\Contracts\HasSumitCustomer {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $organization_id
 * @property string $email
 * @property \App\Enums\OrganizationUserRole $role
 * @property string $token
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvitation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvitation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvitation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvitation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvitation whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvitation whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvitation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvitation whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvitation whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvitation whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationInvitation whereUpdatedAt($value)
 */
	class OrganizationInvitation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property \App\Enums\OrganizationUserRole $role
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Organization $organization
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationUser whereUserId($value)
 */
	class OrganizationUser extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $organization_id
 * @property string $payable_type
 * @property int $payable_id
 * @property int $amount_cents
 * @property string $currency
 * @property \App\Enums\PaymentStatus $status
 * @property string|null $gateway
 * @property string|null $gateway_transaction_id
 * @property array<array-key, mixed>|null $gateway_response
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $account_id
 * @property-read \App\Models\Account|null $account
 * @property-read \App\Models\Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $payable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmountCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereGateway($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereGatewayResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereGatewayTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePayableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePayableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 */
	class Payment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property array<array-key, mixed>|null $limits
 * @property int $price_cents
 * @property string|null $billing_interval
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $product_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EventBilling> $eventsBilling
 * @property-read int|null $events_billing_count
 * @property-read \App\Models\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereBillingInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereLimits($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan wherePriceCents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Plan whereUpdatedAt($value)
 */
	class Plan extends \Eloquent {}
}

namespace App\Models{
/**
 * Product catalog for entitlements. No predefined feature keys.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \App\Enums\ProductStatus $status
 * @property string|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountProduct> $accountProducts
 * @property-read int|null $account_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductEntitlement> $activeEntitlements
 * @property-read int|null $active_entitlements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductLimit> $activeLimits
 * @property-read int|null $active_limits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductFeature> $enabledFeatures
 * @property-read int|null $enabled_features_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductEntitlement> $entitlements
 * @property-read int|null $entitlements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductFeature> $features
 * @property-read int|null $features_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductLimit> $limits
 * @property-read int|null $limits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Plan> $plans
 * @property-read int|null $plans_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductEntitlement> $productEntitlements
 * @property-read int|null $product_entitlements_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductPlan> $productPlans
 * @property-read int|null $product_plans_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UsageRecord> $usageRecords
 * @property-read int|null $usage_records_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product byCategory(?string $category)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product draft()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 */
	class Product extends \Eloquent {}
}

namespace App\Models{
/**
 * Entitlement granted by a product. feature_key is a free-form string.
 *
 * @property int $id
 * @property int $product_id
 * @property string $feature_key
 * @property string|null $value
 * @property array<array-key, mixed>|null $constraints
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $label
 * @property \App\Enums\EntitlementType $type
 * @property bool $is_active
 * @property string|null $description
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountEntitlement> $accountEntitlements
 * @property-read int|null $account_entitlements_count
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement byType(\App\Enums\EntitlementType $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement whereConstraints($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement whereFeatureKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductEntitlement whereValue($value)
 */
	class ProductEntitlement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $product_id
 * @property string $feature_key
 * @property string $label
 * @property string|null $value
 * @property string|null $description
 * @property bool $is_enabled
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature enabled()
 * @method static \Database\Factories\ProductFeatureFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature whereFeatureKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature whereIsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductFeature whereValue($value)
 */
	class ProductFeature extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $product_id
 * @property string $limit_key
 * @property string $label
 * @property string $value
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit active()
 * @method static \Database\Factories\ProductLimitFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit whereLimitKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductLimit whereValue($value)
 */
	class ProductLimit extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $sku
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductPrice> $activePrices
 * @property-read int|null $active_prices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductPrice> $prices
 * @property-read int|null $prices_count
 * @property-read \App\Models\Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountSubscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPlan whereUpdatedAt($value)
 */
	class ProductPlan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $product_plan_id
 * @property string $currency
 * @property int $amount
 * @property \App\Enums\ProductPriceBillingCycle $billing_cycle
 * @property bool $is_active
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductPlan $productPlan
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereBillingCycle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereProductPlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice whereUpdatedAt($value)
 */
	class ProductPrice extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $invitation_id
 * @property int|null $guest_id
 * @property \App\Enums\RsvpResponseType $response
 * @property int|null $attendees_count
 * @property string|null $message
 * @property string|null $ip
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Guest|null $guest
 * @property-read \App\Models\Invitation $invitation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereAttendeesCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereGuestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereInvitationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RsvpResponse whereUserAgent($value)
 */
	class RsvpResponse extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $event_id
 * @property int $guest_id
 * @property int $event_table_id
 * @property string|null $seat_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @property-read \App\Models\EventTable $eventTable
 * @property-read \App\Models\Guest $guest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereEventTableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereGuestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereSeatNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeatAssignment whereUpdatedAt($value)
 */
	class SeatAssignment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $actor_id
 * @property string|null $target_type
 * @property int|null $target_id
 * @property string $action
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $actor
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $target
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereTargetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SystemAuditLog whereUserAgent($value)
 */
	class SystemAuditLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $account_id
 * @property int $product_id
 * @property string $metric_key
 * @property int $quantity
 * @property \Illuminate\Support\Carbon $recorded_at
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\Product $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereMetricKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsageRecord whereRecordedAt($value)
 */
	class UsageRecord extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $current_organization_id
 * @property bool $is_system_admin
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property bool $is_disabled
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\OrganizationUser|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Organization> $organizations
 * @property-read int|null $organizations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Organization> $ownedOrganizations
 * @property-read int|null $owned_organizations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, ?string $guard = null, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsSystemAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastLoginAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, ?string $guard = null)
 */
	class User extends \Eloquent {}
}

