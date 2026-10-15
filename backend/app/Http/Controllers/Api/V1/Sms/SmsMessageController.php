<?php

namespace App\Http\Controllers\Api\V1\Sms;

use App\Enums\SmsType;
use App\Http\Controllers\Controller;
use App\Http\Resources\SmsMessageResource;
use App\Models\SmsMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SmsMessageController extends Controller
{
    /**
     * List the text messages the shop has sent, newest first.
     *
     * No SMS provider is connected, so this log is where messages to customers can be read.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', SmsMessage::class);

        $request->validate([
            'type' => ['nullable', Rule::enum(SmsType::class)],
            /** Digits of the phone number, e.g. "0701" finds +46701… */
            'phone' => ['nullable', 'string', 'max:20'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $digits = preg_replace('/\D/', '', $request->string('phone')->value()) ?? '';
        // Numbers are stored as +46…; a typed leading zero means the same.
        $phoneDigits = str_starts_with($digits, '0') ? '46'.substr($digits, 1) : $digits;

        $messages = SmsMessage::query()
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->enum('type', SmsType::class)))
            ->when($phoneDigits !== '', fn ($query) => $query->whereLike('to', "%{$phoneDigits}%"))
            ->latest('sent_at')
            ->latest('id')
            ->paginate(30);

        return SmsMessageResource::collection($messages);
    }
}
