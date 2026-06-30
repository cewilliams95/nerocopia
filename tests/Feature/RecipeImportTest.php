<?php

use App\Models\User;
use App\Services\RecipeImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function makeHtmlWithJsonLd(array $schema): string
{
    return '<html><head><script type="application/ld+json">'.json_encode($schema).'</script></head></html>';
}

test('parses recipe from JSON-LD', function () {
    $service = new RecipeImportService;

    Http::fake(['*' => Http::response(makeHtmlWithJsonLd([
        '@type' => 'Recipe',
        'name' => 'Chocolate Cake',
        'description' => 'A <b>rich</b> cake.',
        'recipeIngredient' => ['2 cups flour', '1 cup sugar'],
        'recipeInstructions' => [
            ['@type' => 'HowToStep', 'text' => 'Mix dry ingredients.'],
            ['@type' => 'HowToStep', 'text' => 'Bake at 350°F.'],
        ],
    ]))]);

    $result = $service->fromUrl('https://example.com/cake');

    expect($result['title'])->toBe('Chocolate Cake')
        ->and($result['description'])->toBe('A rich cake.')
        ->and($result['ingredients'])->toBe(['2 cups flour', '1 cup sugar'])
        ->and($result['instructions'])->toBe(['Mix dry ingredients.', 'Bake at 350°F.']);
});

test('parses recipe nested inside @graph', function () {
    $service = new RecipeImportService;

    Http::fake(['*' => Http::response(makeHtmlWithJsonLd([
        '@graph' => [
            ['@type' => 'WebPage', 'name' => 'Some Page'],
            ['@type' => 'Recipe', 'name' => 'Graph Cake', 'description' => 'Nested.', 'recipeIngredient' => ['eggs'], 'recipeInstructions' => []],
        ],
    ]))]);

    $result = $service->fromUrl('https://example.com/graph-cake');

    expect($result['title'])->toBe('Graph Cake');
});

test('parses HowToSection instructions', function () {
    $service = new RecipeImportService;

    Http::fake(['*' => Http::response(makeHtmlWithJsonLd([
        '@type' => 'Recipe',
        'name' => 'Sectioned Recipe',
        'description' => '',
        'recipeIngredient' => [],
        'recipeInstructions' => [
            [
                '@type' => 'HowToSection',
                'itemListElement' => [
                    ['@type' => 'HowToStep', 'text' => 'Step one.'],
                    ['@type' => 'HowToStep', 'text' => 'Step two.'],
                ],
            ],
        ],
    ]))]);

    $result = $service->fromUrl('https://example.com/sectioned');

    expect($result['instructions'])->toBe(['Step one.', 'Step two.']);
});

test('converts decimals to fractions in ingredients', function () {
    $service = new RecipeImportService;

    Http::fake(['*' => Http::response(makeHtmlWithJsonLd([
        '@type' => 'Recipe',
        'name' => 'Fraction Test',
        'description' => '',
        'recipeIngredient' => [
            '1.5 cups flour',
            '0.33333334326744 tsp salt',
            '0.75 cup sugar',
            '2.5 tbsp butter',
            '0.25 tsp vanilla',
        ],
        'recipeInstructions' => [],
    ]))]);

    $result = $service->fromUrl('https://example.com/fractions');

    expect($result['ingredients'])->toBe([
        '1 1/2 cups flour',
        '1/3 tsp salt',
        '3/4 cup sugar',
        '2 1/2 tbsp butter',
        '1/4 tsp vanilla',
    ]);
});

test('throws when no recipe schema found', function () {
    $service = new RecipeImportService;

    Http::fake(['*' => Http::response('<html><body>No JSON-LD here.</body></html>')]);

    expect(fn () => $service->fromUrl('https://example.com/no-recipe'))
        ->toThrow(RuntimeException::class);
});

test('import url endpoint returns recipe data for authenticated user', function () {
    $user = User::factory()->create();

    Http::fake(['*' => Http::response(makeHtmlWithJsonLd([
        '@type' => 'Recipe',
        'name' => 'Test Recipe',
        'description' => 'A test.',
        'recipeIngredient' => ['water'],
        'recipeInstructions' => [['@type' => 'HowToStep', 'text' => 'Boil water.']],
    ]))]);

    $response = $this->actingAs($user)->postJson(route('recipes.import-url'), [
        'url' => 'https://example.com/test-recipe',
    ]);

    $response->assertOk()->assertJson([
        'title' => 'Test Recipe',
        'description' => 'A test.',
        'ingredients' => ['water'],
        'instructions' => ['Boil water.'],
    ]);
});

test('import url endpoint returns 422 when no recipe found', function () {
    $user = User::factory()->create();

    Http::fake(['*' => Http::response('<html></html>')]);

    $response = $this->actingAs($user)->postJson(route('recipes.import-url'), [
        'url' => 'https://example.com/nothing',
    ]);

    $response->assertUnprocessable()->assertJsonPath('errors.url.0', 'Could not extract a recipe from the provided URL.');
});

test('import url endpoint requires a valid url', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('recipes.import-url'), [
        'url' => 'not-a-url',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('url');
});

test('import url endpoint requires authentication', function () {
    $response = $this->postJson(route('recipes.import-url'), [
        'url' => 'https://example.com/recipe',
    ]);

    $response->assertUnauthorized();
});
