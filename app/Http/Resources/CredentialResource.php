<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CredentialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'name'         => $this->name,
            'last_used_at' => $this->last_used_at?->toIso8601String(),
            'created_at'   => $this->created_at->toIso8601String(),
            'has_data'     => ! empty($this->data),
        ];
    }
}
