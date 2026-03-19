<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationMembershipResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'is_suspended' => (bool) $this->resource->is_suspended,
            'role' => $this->resource->pivot?->role?->value ?? $this->resource->pivot?->role,
            'is_current' => $this->resource->id === $request->user()?->current_organization_id,
        ];
    }
}
