<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Bentuk JSON gateway untuk API. */
class GatewayResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gateway_code' => $this->gateway_code,
            'name' => $this->name,
            'location' => $this->location,
            'status' => $this->status,
            'mqtt_status' => $this->mqtt_status,
            'internet_status' => $this->internet_status,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'devices_count' => $this->whenCounted('devices'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
