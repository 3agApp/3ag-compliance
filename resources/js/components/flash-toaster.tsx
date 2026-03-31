import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

import type { FlashData, Toast, ToastType } from '@/types';

function isToastType(value: unknown): value is ToastType {
    return ['success', 'error', 'warning', 'info'].includes(String(value));
}

function getToast(flash: unknown): Toast | null {
    if (!flash || typeof flash !== 'object') {
        return null;
    }

    const toast = (flash as FlashData).toast;

    if (!toast || typeof toast !== 'object') {
        return null;
    }

    if (typeof toast.message !== 'string' || !isToastType(toast.type)) {
        return null;
    }

    if (
        toast.description !== undefined &&
        typeof toast.description !== 'string'
    ) {
        return null;
    }

    return toast;
}

function showToast(data: Toast): void {
    const toastFn: Record<ToastType, typeof toast.success> = {
        success: toast.success,
        error: toast.error,
        warning: toast.warning,
        info: toast.info,
    };

    toastFn[data.type](data.message, {
        description: data.description,
    });
}

export function FlashToaster() {
    useEffect(() => {
        return router.on('flash', (event) => {
            const toast = getToast(event.detail.flash);

            if (!toast) {
                return;
            }

            showToast(toast);
        });
    }, []);

    return null;
}
