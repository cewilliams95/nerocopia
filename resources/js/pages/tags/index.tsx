import { Head, useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import RecipeController from '@/actions/App/Http/Controllers/RecipeController';
import type { Tag } from '@/types/tag';

interface Props {
    tags: Tag[];
}

export default function TagsIndex({ tags }: Props) {
    const createForm = useForm({ name: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        createForm.post('/tags', {
            onSuccess: () => createForm.reset(),
        });
    }

    function deleteTag(tag: Tag) {
        if (!window.confirm(`Delete tag "${tag.name}"? It will be removed from all recipes.`)) return;
        createForm.delete(`/tags/${tag.id}`);
    }

    return (
        <>
            <Head title="Tags" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold tracking-tight">Tags</h1>

                <form onSubmit={submit} className="flex max-w-sm items-end gap-2">
                    <div className="flex-1 grid gap-1.5">
                        <Label htmlFor="name">New tag</Label>
                        <Input
                            id="name"
                            value={createForm.data.name}
                            onChange={(e) => createForm.setData('name', e.target.value)}
                            placeholder="e.g. Dinner, Quick, Vegetarian"
                            autoFocus
                        />
                        <InputError message={createForm.errors.name} />
                    </div>
                    <Button type="submit" disabled={createForm.processing}>
                        Create
                    </Button>
                </form>

                {tags.length === 0 ? (
                    <p className="text-sm text-muted-foreground">No tags yet. Create one above.</p>
                ) : (
                    <div className="flex flex-wrap gap-2">
                        {tags.map((tag) => (
                            <span
                                key={tag.id}
                                className="inline-flex items-center gap-1.5 rounded-full border bg-secondary px-3 py-1 text-sm text-secondary-foreground"
                            >
                                {tag.name}
                                {tag.recipes_count !== undefined && (
                                    <span className="text-xs text-muted-foreground">
                                        {tag.recipes_count}
                                    </span>
                                )}
                                <button
                                    type="button"
                                    onClick={() => deleteTag(tag)}
                                    className="ml-0.5 rounded-full p-0.5 hover:bg-destructive/20 hover:text-destructive"
                                    aria-label={`Delete ${tag.name}`}
                                >
                                    <X className="size-3" />
                                </button>
                            </span>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

TagsIndex.layout = {
    breadcrumbs: [
        { title: 'Recipes', href: RecipeController.index.url() },
        { title: 'Tags', href: '/tags' },
    ],
};
