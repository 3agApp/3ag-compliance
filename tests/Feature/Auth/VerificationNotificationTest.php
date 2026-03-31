<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::emailVerification());
});

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('does not send verification notification if email is verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard', absolute: false));

    Notification::assertNothingSent();
});

test('verification notice converts status messages into inertia flash data', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->withSession(['status' => Fortify::VERIFICATION_LINK_SENT])
        ->get(route('verification.notice'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/verify-email')
            ->hasFlash('toast.type', 'success')
            ->hasFlash('toast.message', 'A new verification link has been sent to the email address you provided during registration.')
            ->missing('status'),
        );
});
