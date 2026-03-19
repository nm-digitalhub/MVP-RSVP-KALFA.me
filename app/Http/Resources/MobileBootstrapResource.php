<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileBootstrapResource extends JsonResource
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
            'user' => new CurrentUserResource($this['user']),
            'current_organization' => $this['current_organization'] === null
                ? null
                : new OrganizationContextResource($this['current_organization']),
            'memberships' => OrganizationMembershipResource::collection($this['memberships']),
            'abilities' => $this['abilities'],
            'flags' => $this['flags'],
            'server_time' => $this['server_time'],
        ];
    }
}
