import { Head, Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { edit, index, show } from '@/routes/suppliers';
import type { Supplier } from '@/types';

type Props = {
    supplier: Supplier;
};

function Detail({ label, value }: { label: string; value: string | null }) {
    return (
        <div className="grid gap-1">
            <dt className="text-sm font-medium text-muted-foreground">{label}</dt>
            <dd className="text-sm">{value ?? '—'}</dd>
        </div>
    );
}

export default function SuppliersShow({ supplier }: Props) {
    return (
        <>
            <Head title={supplier.name} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title={supplier.name}
                        description={`Supplier ${supplier.supplier_code}`}
                    />
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={index()}>Back to list</Link>
                        </Button>
                        <Button asChild>
                            <Link href={edit(supplier.id)}>
                                <Pencil />
                                Edit
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="max-w-2xl rounded-xl border p-6">
                    <dl className="grid gap-6 sm:grid-cols-2">
                        <Detail label="Supplier Code" value={supplier.supplier_code} />
                        <Detail label="Name" value={supplier.name} />
                        <Detail label="Email" value={supplier.email} />
                        <Detail label="Phone" value={supplier.phone} />
                        <Detail label="Address" value={supplier.address} />
                        <Detail label="Country" value={supplier.country} />
                        <Detail label="Kontor ID" value={supplier.kontor_id} />
                        <div className="grid gap-1">
                            <dt className="text-sm font-medium text-muted-foreground">Status</dt>
                            <dd>
                                {supplier.active === null ? (
                                    <Badge variant="outline">Unknown</Badge>
                                ) : supplier.active ? (
                                    <Badge variant="default">Active</Badge>
                                ) : (
                                    <Badge variant="secondary">Inactive</Badge>
                                )}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </>
    );
}

SuppliersShow.layout = {
    breadcrumbs: [
        {
            title: 'Suppliers',
            href: index(),
        },
        {
            title: 'Supplier Details',
            href: show(0),
        },
    ],
};
