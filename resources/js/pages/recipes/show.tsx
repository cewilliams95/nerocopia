import { Head, Link } from '@inertiajs/react';
import RecipeController from '@/actions/App/Http/Controllers/RecipeController';
import { Button } from '@/components/ui/button';
import type { Changes } from '@/types/changes';
import type { Recipe } from '@/types/recipe';

interface ShowProps {
    recipe: Recipe;
    changes: Changes;
}

export default function RecipesShow({ recipe, changes }: ShowProps) {
    return (
        <>
            <Head title={recipe.title} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <h1 className="text-xl font-semibold tracking-tight">
                        {recipe.title}
                    </h1>
                    <div className="flex shrink-0 gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link href={RecipeController.edit.url(recipe)}>
                                Edit
                            </Link>
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

                <p className="text-muted-foreground">{recipe.description}</p>

                {recipe.original_source && (
                    <p className="text-sm text-muted-foreground">
                        Source:{' '}
                        <a
                            href={recipe.original_source}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="underline underline-offset-4 hover:text-foreground"
                        >
                            {new URL(recipe.original_source).hostname}
                        </a>
                    </p>
                )}

                <div className="grid gap-8 md:grid-cols-2">
                    <section>
                        <h2 className="mb-3 font-semibold">Ingredients</h2>
                        <ul className="space-y-2">
                            {recipe.ingredients.map((ingredient, index) => (
                                <li
                                    key={index}
                                    className="flex items-start gap-2 text-sm"
                                >
                                    <span className="mt-0.5 size-1.5 shrink-0 rounded-full bg-foreground/40" />
                                    {ingredient}
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section>
                        <h2 className="mb-3 font-semibold">Instructions</h2>
                        <ol className="space-y-3">
                            {recipe.instructions.map((step, index) => (
                                <li key={index} className="flex gap-3 text-sm">
                                    <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-semibold">
                                        {index + 1}
                                    </span>
                                    <span className="leading-relaxed">
                                        {step}
                                    </span>
                                </li>
                            ))}
                        </ol>
                    </section>
                </div>

                {changes.length > 0 && (
                    <section>
                        <h2 className="mb-4 font-semibold">Change History</h2>
                        <div className="space-y-3">
                            {changes.map((change, index) => (
                                <div key={index} className="rounded-md border p-4">
                                    <p className="mb-2 text-xs font-medium capitalize tracking-wide text-muted-foreground">
                                        {change.field}
                                    </p>
                                    <div className="grid grid-cols-[1fr_auto_1fr] items-start gap-3">
                                        <div className="rounded-md bg-orange-50 px-3 py-2 text-sm text-orange-700 dark:bg-orange-950/30 dark:text-orange-400">
                                            {change.old ?? '—'}
                                        </div>
                                        <span className="mt-2 text-muted-foreground">→</span>
                                        <div className="rounded-md bg-green-50 px-3 py-2 text-sm text-green-700 dark:bg-green-950/30 dark:text-green-400">
                                            {change.new ?? '—'}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}

RecipesShow.layout = {
    breadcrumbs: [
        { title: 'Recipes', href: RecipeController.index.url() },
        { title: 'Recipe', href: '' },
    ],
};
