<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecipeFormRequest;
use App\Models\Recipe;
use App\Services\RecipeImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;
use Throwable;

class RecipeController extends Controller
{
    public function index(Request $request): Response
    {
        $recipes = $request->user()->recipes()
            ->with('tags')
            ->when($request->search, fn ($q, $search) => $q->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
            )
            )
            ->when($request->tags, fn ($q, $tags) => $q->whereHas('tags', fn ($q) => $q->whereIn('id', $tags))
            )
            ->latest()
            ->get();

        return Inertia::render('recipes/index', [
            'recipes' => $recipes,
            'tags' => $request->user()->tags()->orderBy('name')->get(),
            'filters' => $request->only(['search', 'tags']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('recipes/create');
    }

    public function importUrl(Request $request, RecipeImportService $service): JsonResponse
    {
        $request->validate(['url' => ['required', 'url']]);

        try {
            $url = $request->input('url');

            return response()->json(array_merge($service->fromUrl($url), ['original_source' => $url]));
        } catch (Throwable) {
            return response()->json([
                'message' => 'Could not extract a recipe from the provided URL.',
                'errors' => ['url' => ['Could not extract a recipe from the provided URL.']],
            ], 422);
        }
    }

    public function store(RecipeFormRequest $request): RedirectResponse
    {
        $recipe = $request->user()->recipes()->create($request->safe()->except(['tag_names']));
        $this->syncTagNames($request, $recipe);

        return to_route('recipes.index');
    }

    public function show(Recipe $recipe): Response
    {
        Gate::authorize('view', $recipe);

        $recipe->load('tags');

        $changes = Activity::forSubject($recipe)
            ->where('event', 'updated')
            ->latest()
            ->get()
            ->flatMap(fn ($activity) => $this->expandChanges(
                $activity->attribute_changes['attributes'] ?? [],
                $activity->attribute_changes['old'] ?? [],
            ))
            ->values();

        return Inertia::render('recipes/show', [
            'recipe' => $recipe,
            'changes' => $changes,
        ]);
    }

    private function syncTagNames(RecipeFormRequest $request, Recipe $recipe): void
    {
        $tagIds = collect($request->input('tag_names', []))
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->uniqueStrict(fn ($name) => Str::lower($name))
            ->map(fn ($name) => $request->user()->tags()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            )->id)
            ->toArray();

        $recipe->tags()->sync($tagIds);
    }

    /** @return array<int, array{field: string, old: string|null, new: string|null}> */
    private function expandChanges(array $attributes, array $old): array
    {
        $excluded = ['user_id', 'original_source'];
        $changes = [];

        foreach ($attributes as $field => $newValue) {
            if (in_array($field, $excluded)) {
                continue;
            }

            if (is_array($newValue)) {
                $oldArray = $old[$field] ?? [];
                $length = max(count($newValue), count($oldArray));
                $singular = rtrim($field, 's');

                for ($i = 0; $i < $length; $i++) {
                    $oldItem = $oldArray[$i] ?? null;
                    $newItem = $newValue[$i] ?? null;

                    if ($oldItem !== $newItem) {
                        $changes[] = [
                            'field' => $singular.' '.($i + 1),
                            'old' => $oldItem,
                            'new' => $newItem,
                        ];
                    }
                }
            } else {
                $changes[] = [
                    'field' => $field,
                    'old' => $old[$field] ?? null,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    public function edit(Recipe $recipe): Response
    {
        Gate::authorize('update', $recipe);

        return Inertia::render('recipes/edit', [
            'recipe' => $recipe->load('tags'),
        ]);
    }

    public function update(RecipeFormRequest $request, Recipe $recipe): RedirectResponse
    {
        Gate::authorize('update', $recipe);

        $recipe->update($request->safe()->except(['tag_names']));
        $this->syncTagNames($request, $recipe);

        return to_route('recipes.index');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        Gate::authorize('delete', $recipe);

        $recipe->delete();

        return to_route('recipes.index');
    }
}
