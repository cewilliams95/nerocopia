export type Change = {
    field: string;
    old: string | null;
    new: string | null;
};

export type Changes = Change[];
