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
