<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to the login page from recipes', function () {
    $response = $this->get(route('recipes.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the recipes index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('recipes.index'));
    $response->assertOk();
});
