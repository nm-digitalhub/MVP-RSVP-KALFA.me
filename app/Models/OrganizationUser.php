<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationUserRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrganizationUser extends Pivot
{
    protected $table = 'organization_users';

    public $incrementing = true;

    protected $fillable = [
        'organization_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => OrganizationUserRole::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
