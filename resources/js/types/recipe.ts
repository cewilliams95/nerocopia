import type { Tag } from '@/types/tag';

export type Recipe = {
    id: number;
    user_id: number;
    title: string;
    description: string;
    original_source: string | null;
    tags: Tag[];
    ingredients: string[];
    instructions: string[];
    created_at: string;
    updated_at: string;
};
