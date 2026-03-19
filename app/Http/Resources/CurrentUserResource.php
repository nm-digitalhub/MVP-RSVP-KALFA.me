<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentUserResource extends JsonResource
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
            'email' => $this->resource->email,
            'current_organization_id' => $this->resource->current_organization_id,
            'is_system_admin' => (bool) $this->resource->is_system_admin,
            'last_login_at' => $this->resource->last_login_at?->utc()->toJSON(),
        ];
    }
}
