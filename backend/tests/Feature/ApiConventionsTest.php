<?php

it('issues an XSRF cookie for the SPA', function () {
    $this->get('/sanctum/csrf-cookie')
        ->assertNoContent()
        ->assertCookie('XSRF-TOKEN');
});

it('returns JSON errors for unknown API routes', function () {
    $this->get('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

it('answers in the language the client asks for', function (string $language, string $expected) {
    $this->withHeader('Accept-Language', $language)
        ->postJson('/api/v1/auth/forgot-password', [])
        ->assertJsonValidationErrors(['email' => $expected]);
})->with([
    'swedish' => ['sv', 'E-post är obligatoriskt.'],
    'english' => ['en-GB,en;q=0.9', 'The email field is required.'],
]);
