<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CreditSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Immutable ledger entry for account credits and debits.
 *
 * Rules:
 *  - Append-only: never update or delete rows.
 *  - amount_agorot is always positive (type determines direction).
 *  - balance_after_agorot is a snapshot at the time of insert.
 *  - Reversals link back via reference (reference_type = self, reference_id = original tx).
 *
 * @property int $id
 * @property int $account_id
 * @property string $type
 * @property CreditSource $source
 * @property int $amount_agorot
 * @property int $balance_after_agorot
 * @property string $currency
 * @property string|null $description
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property \Illuminate\Support\Carbon|null $expiry_at
 * @property int|null $actor_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Account $account
 * @property-read \App\Models\User|null $actor
 * @property-read Model|\Eloquent|null $reference
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereActorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereAmountAgorot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereBalanceAfterAgorot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereExpiryAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccountCreditTransaction whereType($value)
 * @mixin \Eloquent
 * @mixin IdeHelperAccountCreditTransaction
 */
class AccountCreditTransaction extends Model
{
    /** Append-only — no updated_at. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'account_id',
        'type',
        'source',
        'amount_agorot',
        'balance_after_agorot',
        'currency',
        'description',
        'reference_type',
        'reference_id',
        'expiry_at',
        'actor_id',
    ];

    protected function casts(): array
    {
        return [
            'source' => CreditSource::class,
            'expiry_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** Polymorphic reference: Coupon, Payment, or another AccountCreditTransaction (reversal). */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }

    public function isExpired(): bool
    {
        return $this->expiry_at !== null && $this->expiry_at->isPast();
    }

    public function isReversal(): bool
    {
        return $this->source === CreditSource::Adjustment
            && $this->reference_type === self::class;
    }
}
