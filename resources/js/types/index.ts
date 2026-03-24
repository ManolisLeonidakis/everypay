export interface Merchant {
    id: number;
    name: string;
    email: string;
}

export interface Transaction {
    id: number;
    amount: number;
    currency: string;
    description: string | null;
    card_last_four: string;
    status: 'succeeded' | 'failed';
    psp_reference: string;
    created_at: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface SharedProps {
    auth: {
        merchant: Merchant | null;
    };
    flash: {
        success: string | null;
        error: string | null;
    };
}
