import { Head, Link } from '@inertiajs/react';
import { ExternalLink, Pencil } from 'lucide-react';
import { useState } from 'react';
import { show as publicShow } from '@/actions/App/Http/Controllers/PublicProductController';
import Heading from '@/components/heading';
import ProductCompleteness from '@/components/product-completeness';
import ProductDocuments from '@/components/product-documents';
import ProductImages from '@/components/product-images';
import ProductSafetyEntries from '@/components/product-safety-entries';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { edit, index } from '@/routes/products';
import type { Product } from '@/types';

type Props = {
    product: Product;
    documentTypes: Record<string, string>;
};

const statusVariant: Record<
    string,
    'default' | 'secondary' | 'outline' | 'destructive'
> = {
    open: 'outline',
    in_progress: 'secondary',
    submitted: 'secondary',
    under_review: 'secondary',
    clarification_needed: 'destructive',
    approved: 'default',
    rejected: 'destructive',
    completed: 'default',
};

const statusLabel: Record<string, string> = {
    open: 'Open',
    in_progress: 'In progress',
    submitted: 'Submitted',
    under_review: 'Under review',
    clarification_needed: 'Clarification needed',
    approved: 'Approved',
    rejected: 'Rejected',
    completed: 'Completed',
};

export default function ProductsShow({ product, documentTypes }: Props) {
    const [activeTab, setActiveTab] = useState('images');

    return (
        <>
            <Head title={product.name} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title={product.name}
                        description={
                            product.internal_article_number
                                ? `Article ${product.internal_article_number}`
                                : 'Product details'
                        }
                    />
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <a
                                href={publicShow.url(product.public_uuid)}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <ExternalLink className="size-4" />
                                Public View
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={edit(product.id)}>
                                <Pencil className="size-4" />
                                Edit
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={index()}>Back to list</Link>
                        </Button>
                    </div>
                </div>

                <ProductCompleteness
                    score={product.completeness_score}
                    sealStatus={product.seal_status}
                />

                <div className="rounded-xl border p-6">
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <Detail label="Name" value={product.name} />
                        <Detail
                            label="Internal Article No."
                            value={product.internal_article_number}
                        />
                        <Detail
                            label="Supplier Article No."
                            value={product.supplier_article_number}
                        />
                        <Detail
                            label="Order Number"
                            value={product.order_number}
                        />
                        <Detail label="EAN" value={product.ean} mono />
                        <Detail
                            label="Kontor ID"
                            value={product.kontor_id}
                            mono
                        />
                        <Detail
                            label="Supplier"
                            value={product.supplier?.name}
                        />
                        <Detail label="Brand" value={product.brand?.name} />
                        <Detail
                            label="Category"
                            value={product.category?.name}
                        />
                        <Detail
                            label="Template"
                            value={product.template?.name}
                        />
                        <div>
                            <dt className="text-sm text-muted-foreground">
                                Status
                            </dt>
                            <dd className="mt-1">
                                {product.status ? (
                                    <Badge
                                        variant={
                                            statusVariant[product.status] ??
                                            'outline'
                                        }
                                    >
                                        {statusLabel[product.status] ??
                                            product.status}
                                    </Badge>
                                ) : (
                                    <span className="text-sm text-muted-foreground">
                                        —
                                    </span>
                                )}
                            </dd>
                        </div>
                    </div>
                </div>

                <div className="rounded-xl border p-6">
                    <Tabs value={activeTab} onValueChange={setActiveTab}>
                        <TabsList>
                            <TabsTrigger value="images">Images</TabsTrigger>
                            <TabsTrigger value="documents">
                                Documents
                            </TabsTrigger>
                            <TabsTrigger value="safety">
                                Safety Data
                            </TabsTrigger>
                        </TabsList>
                        <TabsContent value="images">
                            <ProductImages
                                productId={product.id}
                                initialImages={product.images ?? []}
                            />
                        </TabsContent>
                        <TabsContent value="documents">
                            <ProductDocuments
                                productId={product.id}
                                documentTypes={documentTypes}
                                initialDocuments={product.documents ?? []}
                            />
                        </TabsContent>
                        <TabsContent value="safety">
                            <ProductSafetyEntries
                                productId={product.id}
                                initialSafetyEntry={
                                    product.safety_entry ?? null
                                }
                                requiredDataFields={
                                    product.template?.required_data_fields ?? []
                                }
                            />
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </>
    );
}

function Detail({
    label,
    value,
    mono = false,
}: {
    label: string;
    value?: string | null;
    mono?: boolean;
}) {
    return (
        <div>
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd
                className={`mt-1 text-sm font-medium ${mono ? 'font-mono' : ''}`}
            >
                {value ?? (
                    <span className="text-muted-foreground font-normal">—</span>
                )}
            </dd>
        </div>
    );
}

ProductsShow.layout = {
    breadcrumbs: [
        {
            title: 'Products',
            href: index.url(),
        },
        {
            title: 'View Product',
            href: '#',
        },
    ],
};
