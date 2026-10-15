<?php

namespace App\Http\Resources;

use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Customer
 */
class PublicCustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
        ];
    }

    /**
     * Signing in creates the customer on first visit, but to the client it is a sign-in,
     * not the creation of a resource, so the status stays 200 rather than 201.
     */
    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode(JsonResponse::HTTP_OK);
    }
}
