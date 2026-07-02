<?php

use App\Models\Recipe;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('authenticated users can view their tags', function () {
    $user = User::factory()->create();
    Tag::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('tags.index'))
        ->assertOk()
        ->assertInertia(fn ($assert) => $assert
            ->component('tags/index')
            ->has('tags', 3)
        );
});

test('guests are redirected from tags index', function () {
    $this->get(route('tags.index'))->assertRedirect(route('login'));
});

test('users can create a tag', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('tags.store'), ['name' => 'Dinner'])
        ->assertRedirect();

    expect(Tag::where('user_id', $user->id)->where('slug', 'dinner')->exists())->toBeTrue();
});

test('creating a tag with a duplicate name reuses the existing tag', function () {
    $user = User::factory()->create();
    Tag::factory()->create(['user_id' => $user->id, 'name' => 'Dinner', 'slug' => 'dinner']);

    $this->actingAs($user)
        ->post(route('tags.store'), ['name' => 'dinner'])
        ->assertRedirect();

    expect(Tag::where('user_id', $user->id)->where('slug', 'dinner')->count())->toBe(1);
});

test('users can delete their own tag', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('tags.destroy', $tag))
        ->assertRedirect();

    expect(Tag::find($tag->id))->toBeNull();
});

test('users cannot delete other users tags', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create();

    $this->actingAs($user)
        ->delete(route('tags.destroy', $tag))
        ->assertForbidden();
});

test('tags can be assigned to a recipe on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'title' => 'Tagged Recipe',
            'description' => 'A recipe with tags.',
            'ingredients' => ['flour'],
            'instructions' => ['mix'],
            'tag_names' => ['Dinner', 'Quick'],
        ])
        ->assertRedirect(route('recipes.index'));

    $recipe = Recipe::where('title', 'Tagged Recipe')->first();
    expect($recipe->tags)->toHaveCount(2);
    expect($recipe->tags->pluck('name')->sort()->values()->toArray())->toBe(['Dinner', 'Quick']);
});

test('submitting a tag name that already exists reuses the existing tag', function () {
    $user = User::factory()->create();
    $existing = Tag::factory()->create(['user_id' => $user->id, 'name' => 'Dinner', 'slug' => 'dinner']);

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'title' => 'Tagged Recipe',
            'description' => 'desc',
            'ingredients' => ['flour'],
            'instructions' => ['mix'],
            'tag_names' => ['Dinner'],
        ])
        ->assertRedirect(route('recipes.index'));

    expect(Tag::where('user_id', $user->id)->where('slug', 'dinner')->count())->toBe(1);
    $recipe = Recipe::where('title', 'Tagged Recipe')->first();
    expect($recipe->tags->first()->id)->toBe($existing->id);
});

test('tags can be updated on a recipe', function () {
    $user = User::factory()->create();
    $tagA = Tag::factory()->create(['user_id' => $user->id, 'name' => 'Breakfast', 'slug' => 'breakfast']);
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);
    $recipe->tags()->sync([$tagA->id]);

    $this->actingAs($user)
        ->put(route('recipes.update', $recipe), [
            'title' => $recipe->title,
            'description' => $recipe->description,
            'ingredients' => $recipe->ingredients,
            'instructions' => $recipe->instructions,
            'tag_names' => ['Dinner'],
        ])
        ->assertRedirect(route('recipes.index'));

    expect($recipe->fresh()->tags)->toHaveCount(1);
    expect($recipe->fresh()->tags->first()->name)->toBe('Dinner');
});

test('recipes can be filtered by tag on index', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create(['user_id' => $user->id]);
    $tagged = Recipe::factory()->create(['user_id' => $user->id]);
    $tagged->tags()->sync([$tag->id]);
    Recipe::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('recipes.index', ['tags' => [$tag->id]]))
        ->assertOk()
        ->assertInertia(fn ($assert) => $assert
            ->has('recipes', 1)
            ->where('recipes.0.id', $tagged->id)
        );
});

test('recipes can be filtered by search on index', function () {
    $user = User::factory()->create();
    Recipe::factory()->create(['user_id' => $user->id, 'title' => 'Chicken Tikka']);
    Recipe::factory()->create(['user_id' => $user->id, 'title' => 'Beef Stew']);

    $this->actingAs($user)
        ->get(route('recipes.index', ['search' => 'chicken']))
        ->assertOk()
        ->assertInertia(fn ($assert) => $assert->has('recipes', 1));
});

test('duplicate tag names are deduplicated on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('recipes.store'), [
            'title' => 'Test',
            'description' => 'desc',
            'ingredients' => ['flour'],
            'instructions' => ['mix'],
            'tag_names' => ['Dinner', 'dinner', 'DINNER'],
        ])
        ->assertRedirect(route('recipes.index'));

    expect(Tag::where('user_id', $user->id)->where('slug', 'dinner')->count())->toBe(1);
});
