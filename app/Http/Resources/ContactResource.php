<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'street_address' => $this->street_address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'ni_number' => $this->ni_number,
            'bank' => $this->bank,
            'account_number' => $this->account_number,
            'sort_code' => $this->sort_code,
            'date_of_birth' => $this->date_of_birth,
            'marital_status' => $this->marital_status,
            'gender' => $this->gender,
            'companies' => $this->whenLoaded('companies'),
            'deals' => $this->whenLoaded('deals', fn () => $this->deals->map(
                fn ($deal) => array_merge(
                    $deal->toArray(),
                    ['pivot' => ['is_primary' => (bool) $deal->pivot?->is_primary]],
                ),
            )),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
