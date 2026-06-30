import { Head, Link } from '@inertiajs/react';
import RecipeController from '@/actions/App/Http/Controllers/RecipeController';
import { Button } from '@/components/ui/button';
import type { Recipe } from '@/types/recipe';

export default function RecipesIndex({ recipes }: { recipes: Recipe[] }) {
    return (
        <>
            <Head title="My Recipes" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold tracking-tight">My Recipes</h1>
                    <Button asChild>
                        <Link href={RecipeController.create.url()} prefetch>
                            New Recipe
                        </Link>
                    </Button>
                </div>

                {recipes.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed p-12 text-center">
                        <p className="text-muted-foreground">No recipes yet.</p>
                        <Button asChild className="mt-4">
                            <Link href={RecipeController.create.url()}>Create your first recipe</Link>
                        </Button>
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {recipes.map((recipe) => (
                            <div
                                key={recipe.id}
                                className="flex flex-col gap-3 rounded-xl border p-5 shadow-xs"
                            >
                                <div className="flex-1">
                                    <h2 className="font-semibold">{recipe.title}</h2>
                                    <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                        {recipe.description}
                                    </p>
                                </div>

                                <p className="text-xs text-muted-foreground">
                                    {recipe.ingredients.length} ingredient{recipe.ingredients.length !== 1 ? 's' : ''} &middot;{' '}
                                    {recipe.instructions.length} step{recipe.instructions.length !== 1 ? 's' : ''}
                                </p>

                                <div className="flex gap-2">
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={RecipeController.show.url(recipe)}>View</Link>
                                    </Button>
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={RecipeController.edit.url(recipe)}>Edit</Link>
                                    </Button>
                                    <Link
                                        href={RecipeController.destroy.url(recipe)}
                                        method="delete"
                                        as="button"
                                        className="inline-flex h-8 items-center justify-center rounded-md bg-destructive px-3 text-sm font-medium text-white shadow-xs hover:bg-destructive/90 disabled:pointer-events-none disabled:opacity-50"
                                        onClick={(e) => {
                                            if (!window.confirm('Delete this recipe?')) {
                                                e.preventDefault();
                                            }
                                        }}
                                    >
                                        Delete
                                    </Link>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

RecipesIndex.layout = {
    breadcrumbs: [{ title: 'Recipes', href: RecipeController.index.url() }],
};
