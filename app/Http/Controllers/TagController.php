<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('tags/index', [
            'tags' => $request->user()->tags()->withCount('recipes')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:50']]);

        $name = trim($request->input('name'));

        $request->user()->tags()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name],
        );

        return back();
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        Gate::authorize('delete', $tag);

        $tag->delete();

        return back();
    }
}
