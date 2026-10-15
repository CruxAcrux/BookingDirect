<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Booking\Actions\FindOrCreateCustomer;
use App\Customers\OtpService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\RequestOtpRequest;
use App\Http\Requests\Public\VerifyOtpRequest;
use App\Http\Resources\PublicCustomerResource;
use App\Models\Customer;
use App\Models\Shop;
use App\Tenancy\CurrentShop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CustomerSessionController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    /**
     * Text a six-digit sign-in code to a phone number.
     *
     * Limited to three codes per number every ten minutes. In demo mode (no real SMS
     * provider) the response also contains the code as `demo_code`.
     *
     * @unauthenticated
     */
    public function requestCode(RequestOtpRequest $request, Shop $shop): JsonResponse
    {
        $code = $this->otp->send($shop, $request->phone());

        return response()->json([
            'expires_in_minutes' => OtpService::TTL_MINUTES,
            /** @var string|null */
            'demo_code' => config('booking.otp_demo_mode') ? $code : null,
        ], 202);
    }

    /**
     * Sign in with the code. The customer is created on first sign-in.
     *
     * @unauthenticated
     */
    public function verify(VerifyOtpRequest $request, Shop $shop, FindOrCreateCustomer $customers): PublicCustomerResource
    {
        $this->otp->verify($request->phone(), $request->string('code')->value());

        $customer = $customers->handle($request->phone(), locale: app()->getLocale());

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return new PublicCustomerResource($customer);
    }

    /**
     * The signed-in customer of this shop.
     *
     * @unauthenticated
     */
    public function me(Request $request, Shop $shop): PublicCustomerResource
    {
        return new PublicCustomerResource(self::customer($request));
    }

    /**
     * Sign the customer out.
     *
     * @unauthenticated
     */
    public function logout(Request $request, Shop $shop): Response
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    /**
     * The signed-in customer. A customer signed in at another shop counts as signed out here.
     */
    public static function customer(Request $request): Customer
    {
        $customer = $request->user('customer');

        abort_unless(
            $customer instanceof Customer && $customer->shop_id === app(CurrentShop::class)->id(),
            Response::HTTP_UNAUTHORIZED,
            __('Unauthenticated.'),
        );

        return $customer;
    }
}
