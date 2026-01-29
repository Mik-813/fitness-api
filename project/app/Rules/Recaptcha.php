<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        $data = $response->json();

        if (! $response->successful() || ! ($data['success'] ?? false)) {
            $fail('The reCAPTCHA verification failed.');
            return;
        }

        if (($data['score'] ?? 0) < config('services.recaptcha.min_score', 0.5)) {
            $fail('The reCAPTCHA score is too low.');
        }
    }
}
