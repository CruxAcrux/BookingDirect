<?php

namespace App\Http\Controllers\Api\V1\Customers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicCustomerResource;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CustomerSearchController extends Controller
{
    private const LIMIT = 10;

    /**
     * Find customers by name or phone number, for booking on someone's behalf.
     *
     * Digits are matched anywhere in the number, so "0701" finds +46701…
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Booking::class);

        $request->validate(['search' => ['required', 'string', 'min:2', 'max:60']]);

        $search = $request->string('search')->trim()->value();
        $digits = preg_replace('/\D/', '', $search) ?? '';
        // Swedish numbers are stored as +46…; a typed leading zero means the same.
        $phoneDigits = str_starts_with($digits, '0') ? '46'.substr($digits, 1) : $digits;

        $customers = Customer::query()
            ->where(function ($query) use ($search, $phoneDigits): void {
                $query->whereLike('name', "%{$search}%");

                if (strlen($phoneDigits) >= 3) {
                    $query->orWhereLike('phone', "%{$phoneDigits}%");
                }
            })
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get();

        return PublicCustomerResource::collection($customers);
    }
}
