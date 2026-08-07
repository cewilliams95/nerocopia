<?php

use App\Models\Recipe;
use App\Models\User;

test('guests are redirected from recipes index', function () {
    $this->get(route('recipes.index'))->assertRedirect(route('login'));
});

test('authenticated users can view their recipes', function () {
    $user = User::factory()->create();
    $recipes = Recipe::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('recipes.index'))
        ->assertOk()
        ->assertInertia(fn ($assert) => $assert
            ->component('recipes/index')
            ->has('recipes', 3)
        );
});

test('users only see their own recipes on index', function () {
    $user = User::factory()->create();
    Recipe::factory()->count(2)->create(['user_id' => $user->id]);
    Recipe::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('recipes.index'))
        ->assertOk()
        ->assertInertia(fn ($assert) => $assert
            ->has('recipes', 2)
        );
});

test('authenticated users can view the create recipe page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('recipes.create'))
        ->assertOk()
        ->assertInertia(fn ($assert) => $assert->component('recipes/create'));
});

test('users can create a recipe', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'title' => 'Chocolate Cake',
            'description' => 'A delicious chocolate cake recipe.',
            'ingredients' => ['2 cups flour', '1 cup sugar', '1/2 cup cocoa'],
            'instructions' => ['Mix dry ingredients', 'Add wet ingredients', 'Bake at 350F for 30 min'],
        ])
        ->assertRedirect(route('recipes.index'));

    $recipe = Recipe::where('user_id', $user->id)->where('title', 'Chocolate Cake')->first();
    expect($recipe)->not->toBeNull();
    expect($recipe->ingredients)->toHaveCount(3);
    expect($recipe->instructions)->toHaveCount(3);
});

test('original_source is stored when provided on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'title' => 'Imported Cake',
            'description' => 'From the web.',
            'original_source' => 'https://example.com/recipe/chocolate-cake',
            'ingredients' => ['flour'],
            'instructions' => ['mix'],
        ])
        ->assertRedirect(route('recipes.index'));

    $recipe = Recipe::where('user_id', $user->id)->where('title', 'Imported Cake')->first();
    expect($recipe->original_source)->toBe('https://example.com/recipe/chocolate-cake');
});

test('original_source is null when not provided on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'title' => 'Manual Cake',
            'description' => 'Typed by hand.',
            'ingredients' => ['flour'],
            'instructions' => ['mix'],
        ])
        ->assertRedirect(route('recipes.index'));

    $recipe = Recipe::where('user_id', $user->id)->where('title', 'Manual Cake')->first();
    expect($recipe->original_source)->toBeNull();
});

test('recipe creation requires a title', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('recipes.store'), [
            'title' => '',
            'description' => 'desc',
            'ingredients' => ['flour'],
            'instructions' => ['mix'],
        ])
        ->assertSessionHasErrors('title');
});

test('recipe creation requires at least one ingredient', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('recipes.store'), [
            'title' => 'Test',
            'description' => 'desc',
            'ingredients' => [],
            'instructions' => ['mix'],
        ])
        ->assertSessionHasErrors('ingredients');
});

test('users can view their own recipe', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('recipes.show', $recipe))
        ->assertOk()
        ->assertInertia(fn ($assert) => $assert
            ->component('recipes/show')
            ->has('recipe')
        );
});

test('users cannot view other users recipes', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    $this->actingAs($user)
        ->get(route('recipes.show', $recipe))
        ->assertForbidden();
});

test('users can update their own recipe', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->put(route('recipes.update', $recipe), [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'ingredients' => ['new ingredient'],
            'instructions' => ['new step'],
        ])
        ->assertRedirect(route('recipes.index'));

    expect($recipe->fresh()->title)->toBe('Updated Title');
});

test('users cannot update other users recipes', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    $this->actingAs($user)
        ->put(route('recipes.update', $recipe), [
            'title' => 'Hacked',
            'description' => 'desc',
            'ingredients' => ['ingredient'],
            'instructions' => ['step'],
        ])
        ->assertForbidden();
});

test('users can delete their own recipe', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('recipes.destroy', $recipe))
        ->assertRedirect(route('recipes.index'));

    expect(Recipe::find($recipe->id))->toBeNull();
});

test('users cannot delete other users recipes', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create();

    $this->actingAs($user)
        ->delete(route('recipes.destroy', $recipe))
        ->assertForbidden();

    expect(Recipe::find($recipe->id))->not->toBeNull();
});
