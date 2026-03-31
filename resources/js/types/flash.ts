export type ToastType = 'success' | 'error' | 'warning' | 'info';

export type Toast = {
    type: ToastType;
    message: string;
    description?: string;
};

export type FlashData = {
    toast?: Toast;
};
