import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import SupplierForm from '@/components/supplier-form';
import { Button } from '@/components/ui/button';
import { edit, index, update } from '@/routes/suppliers';
import type { Supplier } from '@/types';

type Props = {
    supplier: Supplier;
};

export default function SuppliersEdit({ supplier }: Props) {
    return (
        <>
            <Head title={`Edit ${supplier.name}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Edit Supplier"
                        description={`Update details for ${supplier.name}`}
                    />
                    <Button variant="outline" asChild>
                        <Link href={index()}>Back to list</Link>
                    </Button>
                </div>

                <div className="max-w-2xl rounded-xl border p-6">
                    <SupplierForm
                        formProps={update.form(supplier.id)}
                        supplier={supplier}
                        submitLabel="Update Supplier"
                    />
                </div>
            </div>
        </>
    );
}

SuppliersEdit.layout = {
    breadcrumbs: [
        {
            title: 'Suppliers',
            href: index(),
        },
        {
            title: 'Edit Supplier',
            href: edit(0),
        },
    ],
};
