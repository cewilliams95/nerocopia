import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useRef, useState } from 'react';
import RecipeController from '@/actions/App/Http/Controllers/RecipeController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { Recipe } from '@/types/recipe';
import type { Tag } from '@/types/tag';

interface Props {
    recipes: Recipe[];
    tags: Tag[];
    filters: {
        search?: string;
        tags?: string[];
    };
}

export default function RecipesIndex({ recipes, tags, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [selectedTags, setSelectedTags] = useState<number[]>(
        filters.tags ? filters.tags.map(Number) : [],
    );
    const searchTimeout = useRef<ReturnType<typeof setTimeout>>(null);

    function applyFilters(newSearch: string, newTags: number[]) {
        router.get(
            RecipeController.index.url(),
            {
                search: newSearch || undefined,
                tags: newTags.length ? newTags : undefined,
            },
            { preserveState: true, replace: true },
        );
    }

    function handleSearch(e: React.ChangeEvent<HTMLInputElement>) {
        const value = e.target.value;
        setSearch(value);
        if (searchTimeout.current) clearTimeout(searchTimeout.current);
        searchTimeout.current = setTimeout(() => applyFilters(value, selectedTags), 300);
    }

    function toggleTag(id: number) {
        const newTags = selectedTags.includes(id)
            ? selectedTags.filter((t) => t !== id)
            : [...selectedTags, id];
        setSelectedTags(newTags);
        applyFilters(search, newTags);
    }

    const isFiltered = search || selectedTags.length > 0;

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

                <div className="flex flex-col gap-3">
                    <div className="relative max-w-sm">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={handleSearch}
                            placeholder="Search recipes…"
                            className="pl-9"
                        />
                    </div>

                    {tags.length > 0 && (
                        <div className="flex flex-wrap gap-2">
                            {tags.map((tag) => {
                                const isSelected = selectedTags.includes(tag.id);
                                return (
                                    <button
                                        key={tag.id}
                                        type="button"
                                        onClick={() => toggleTag(tag.id)}
                                        className={cn(
                                            'inline-flex items-center rounded-full border px-3 py-1 text-sm font-medium transition-colors',
                                            isSelected
                                                ? 'border-accent bg-accent text-accent-foreground'
                                                : 'border-border bg-background text-foreground hover:bg-muted',
                                        )}
                                    >
                                        {tag.name}
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </div>

                {recipes.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed p-12 text-center">
                        {isFiltered ? (
                            <p className="text-muted-foreground">No recipes match your filters.</p>
                        ) : (
                            <>
                                <p className="text-muted-foreground">No recipes yet.</p>
                                <Button asChild className="mt-4">
                                    <Link href={RecipeController.create.url()}>
                                        Create your first recipe
                                    </Link>
                                </Button>
                            </>
                        )}
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

                                {recipe.tags.length > 0 && (
                                    <div className="flex flex-wrap gap-1.5">
                                        {recipe.tags.map((tag) => (
                                            <Badge key={tag.id} variant="secondary">
                                                {tag.name}
                                            </Badge>
                                        ))}
                                    </div>
                                )}

                                <p className="text-xs text-muted-foreground">
                                    {recipe.ingredients.length} ingredient
                                    {recipe.ingredients.length !== 1 ? 's' : ''} &middot;{' '}
                                    {recipe.instructions.length} step
                                    {recipe.instructions.length !== 1 ? 's' : ''}
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
