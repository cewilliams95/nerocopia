import { Head, useForm } from '@inertiajs/react';
import { Plus, X } from 'lucide-react';
import { useState } from 'react';
import RecipeController from '@/actions/App/Http/Controllers/RecipeController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Recipe } from '@/types/recipe';

export default function RecipesEdit({ recipe }: { recipe: Recipe }) {
    const { data, setData, put, processing, errors } = useForm({
        title: recipe.title,
        description: recipe.description,
        tag_names: recipe.tags.map((t) => t.name),
        ingredients: recipe.ingredients,
        instructions: recipe.instructions,
    });

    const [tagInput, setTagInput] = useState('');

    function addTag() {
        const name = tagInput.trim();
        if (!name) return;
        const isDuplicate = data.tag_names.some(
            (t) => t.toLowerCase() === name.toLowerCase(),
        );
        if (!isDuplicate) {
            setData('tag_names', [...data.tag_names, name]);
        }
        setTagInput('');
    }

    function removeTag(name: string) {
        setData(
            'tag_names',
            data.tag_names.filter((t) => t !== name),
        );
    }

    function handleTagKeyDown(e: React.KeyboardEvent<HTMLInputElement>) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addTag();
        }
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        put(RecipeController.update.url(recipe));
    }

    function updateList(field: 'ingredients' | 'instructions', index: number, value: string) {
        const updated = [...data[field]];
        updated[index] = value;
        setData(field, updated);
    }

    function addItem(field: 'ingredients' | 'instructions') {
        setData(field, [...data[field], '']);
    }

    function removeItem(field: 'ingredients' | 'instructions', index: number) {
        setData(field, data[field].filter((_, i) => i !== index));
    }

    return (
        <>
            <Head title={`Edit: ${recipe.title}`} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">Edit Recipe</h1>

                <form onSubmit={submit} className="max-w-2xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            name="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            placeholder="Recipe title"
                            autoFocus
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            name="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="What is this recipe about?"
                            rows={3}
                            className="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Tags</Label>
                        <div className="flex gap-2">
                            <Input
                                value={tagInput}
                                onChange={(e) => setTagInput(e.target.value)}
                                onKeyDown={handleTagKeyDown}
                                placeholder="Add a tag…"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                onClick={addTag}
                                aria-label="Add tag"
                            >
                                <Plus className="size-4" />
                            </Button>
                        </div>
                        {data.tag_names.length > 0 && (
                            <div className="flex flex-wrap gap-2">
                                {data.tag_names.map((name) => (
                                    <span
                                        key={name}
                                        className="inline-flex items-center gap-1.5 rounded-full border bg-secondary px-3 py-1 text-sm text-secondary-foreground"
                                    >
                                        {name}
                                        <button
                                            type="button"
                                            onClick={() => removeTag(name)}
                                            className="rounded-full p-0.5 hover:bg-destructive/20 hover:text-destructive"
                                            aria-label={`Remove ${name}`}
                                        >
                                            <X className="size-3" />
                                        </button>
                                    </span>
                                ))}
                            </div>
                        )}
                    </div>

                    <div className="grid gap-3">
                        <Label>Ingredients</Label>
                        {data.ingredients.map((ingredient, index) => (
                            <div key={index} className="flex gap-2">
                                <Input
                                    value={ingredient}
                                    onChange={(e) => updateList('ingredients', index, e.target.value)}
                                    placeholder={`Ingredient ${index + 1}`}
                                />
                                {data.ingredients.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => removeItem('ingredients', index)}
                                        className="shrink-0"
                                    >
                                        Remove
                                    </Button>
                                )}
                            </div>
                        ))}
                        <InputError message={errors.ingredients} />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => addItem('ingredients')}
                            className="self-start"
                        >
                            Add Ingredient
                        </Button>
                    </div>

                    <div className="grid gap-3">
                        <Label>Instructions</Label>
                        {data.instructions.map((instruction, index) => (
                            <div key={index} className="flex gap-2">
                                <div className="flex flex-1 gap-2">
                                    <span className="mt-2 text-sm font-medium text-muted-foreground w-5 shrink-0">
                                        {index + 1}.
                                    </span>
                                    <textarea
                                        value={instruction}
                                        onChange={(e) => updateList('instructions', index, e.target.value)}
                                        placeholder={`Step ${index + 1}`}
                                        rows={2}
                                        className="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[60px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50"
                                    />
                                </div>
                                {data.instructions.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => removeItem('instructions', index)}
                                        className="mt-1 shrink-0 self-start"
                                    >
                                        Remove
                                    </Button>
                                )}
                            </div>
                        ))}
                        <InputError message={errors.instructions} />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => addItem('instructions')}
                            className="self-start"
                        >
                            Add Step
                        </Button>
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href={RecipeController.show.url(recipe)}>Cancel</a>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

RecipesEdit.layout = {
    breadcrumbs: [
        { title: 'Recipes', href: RecipeController.index.url() },
        { title: 'Edit Recipe', href: '' },
    ],
};
