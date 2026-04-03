import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import SupplierForm from '@/components/supplier-form';
import { Button } from '@/components/ui/button';
import { store, index, create } from '@/routes/suppliers';

export default function SuppliersCreate() {
    return (
        <>
            <Head title="Add Supplier" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Add Supplier"
                        description="Create a new supplier in the directory"
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back to list</Link>
                    </Button>
                </div>

                <div className="max-w-2xl rounded-xl border p-6">
                    <SupplierForm
                        formProps={store.form()}
                        submitLabel="Create Supplier"
                    />
                </div>
            </div>
        </>
    );
}

SuppliersCreate.layout = {
    breadcrumbs: [
        {
            title: 'Suppliers',
            href: index(),
        },
        {
            title: 'Add Supplier',
            href: create(),
        },
    ],
};
