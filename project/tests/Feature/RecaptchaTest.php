<?php

namespace Tests\Feature;

use App\Rules\Recaptcha;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaTest extends TestCase
{
    public function test_validation_passes_when_success_and_high_score()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'score' => 0.9], 200),
        ]);

        $rule = new Recaptcha();

        $failCalled = false;
        $rule->validate('recaptcha_token', 'dummy_token', function ($message) use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertFalse($failCalled);
    }

    public function test_validation_fails_when_success_is_false()
    {
        Http::fake([
            '*' => Http::response(['success' => false, 'score' => 0.9], 200),
        ]);

        $rule = new Recaptcha();

        $failCalled = false;
        $rule->validate('recaptcha_token', 'dummy_token', function ($message) use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertTrue($failCalled);
    }

    public function test_validation_fails_when_score_is_too_low()
    {
        Http::fake([
            '*' => Http::response(['success' => true, 'score' => 0.1], 200),
        ]);

        $rule = new Recaptcha();

        $failCalled = false;
        $rule->validate('recaptcha_token', 'dummy_token', function ($message) use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertTrue($failCalled);
    }

    public function test_validation_fails_when_http_request_fails()
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $rule = new Recaptcha();

        $failCalled = false;
        $rule->validate('recaptcha_token', 'dummy_token', function ($message) use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertTrue($failCalled);
    }
}