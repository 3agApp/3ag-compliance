export type Supplier = {
    id: number;
    supplier_code: string;
    name: string;
    address: string | null;
    country: string | null;
    email: string | null;
    phone: string | null;
    active: boolean | null;
    kontor_id: string | null;
    created_at: string;
    updated_at: string;
};

export type PaginatedData<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};
