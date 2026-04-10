import { Head } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Clock,
    Download,
    FileText,
    ShieldAlert,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { PublicProduct, SealStatus } from '@/types';

type Props = {
    product: PublicProduct;
};

const sealImages: Record<SealStatus, string> = {
    verified: '/images/SPS_Status_V2/SPS_verified_trans.png',
    in_progress: '/images/SPS_Status_V2/SPS_in_progress_trans.png',
    not_verified: '/images/SPS_Status_V2/SPS_not_verified_trans.png',
};

const sealLabels: Record<SealStatus, string> = {
    verified: 'Verified',
    in_progress: 'In Progress',
    not_verified: 'Not Verified',
};

const sealBadgeVariant: Record<
    SealStatus,
    'default' | 'secondary' | 'outline'
> = {
    verified: 'default',
    in_progress: 'secondary',
    not_verified: 'outline',
};

const sealBadgeClass: Record<SealStatus, string> = {
    verified: 'bg-green-600 text-white',
    in_progress: 'bg-amber-500 text-white',
    not_verified: '',
};

function formatFileSize(bytes: number | null): string {
    if (bytes === null) {
        return '';
    }
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function PublicProductShow({ product }: Props) {
    const [selectedImage, setSelectedImage] = useState(0);
    const hasSafetyData = product.safety_entry && Object.values({
        safety_text: product.safety_entry.safety_text,
        warning_text: product.safety_entry.warning_text,
        age_grading: product.safety_entry.age_grading,
        material_information: product.safety_entry.material_information,
        usage_restrictions: product.safety_entry.usage_restrictions,
        safety_instructions: product.safety_entry.safety_instructions,
        additional_notes: product.safety_entry.additional_notes,
    }).some(Boolean);

    return (
        <>
            <Head title={`${product.name} – Product Safety`} />
            <div className="min-h-screen bg-background text-foreground">
                {/* Header */}
                <header className="border-b bg-card">
                    <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
                        <div className="flex items-center gap-3">
                            <ShieldAlert className="size-6 text-primary" />
                            <span className="text-lg font-semibold">
                                Product Safety Profile
                            </span>
                        </div>
                        <Badge
                            variant={sealBadgeVariant[product.seal_status]}
                            className={cn(
                                'gap-1.5 px-3 py-1 text-sm',
                                sealBadgeClass[product.seal_status],
                            )}
                        >
                            {product.seal_status === 'verified' && (
                                <CheckCircle2 className="size-3.5" />
                            )}
                            {product.seal_status === 'in_progress' && (
                                <Clock className="size-3.5" />
                            )}
                            {product.seal_status === 'not_verified' && (
                                <XCircle className="size-3.5" />
                            )}
                            {sealLabels[product.seal_status]}
                        </Badge>
                    </div>
                </header>

                <main className="mx-auto max-w-5xl px-4 py-8 sm:px-6">
                    {/* Product Hero */}
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-2">
                        {/* Images */}
                        <div className="space-y-3">
                            {product.images.length > 0 ? (
                                <>
                                    <div className="overflow-hidden rounded-xl border bg-muted/30">
                                        <img
                                            src={
                                                product.images[selectedImage]
                                                    ?.url
                                            }
                                            alt={product.name}
                                            className="aspect-square w-full object-contain p-4"
                                        />
                                    </div>
                                    {product.images.length > 1 && (
                                        <div className="flex gap-2 overflow-x-auto">
                                            {product.images.map(
                                                (image, index) => (
                                                    <button
                                                        key={image.id}
                                                        type="button"
                                                        onClick={() =>
                                                            setSelectedImage(
                                                                index,
                                                            )
                                                        }
                                                        className={cn(
                                                            'shrink-0 overflow-hidden rounded-lg border-2 transition-colors',
                                                            selectedImage ===
                                                                index
                                                                ? 'border-primary'
                                                                : 'border-transparent hover:border-muted-foreground/30',
                                                        )}
                                                    >
                                                        <img
                                                            src={
                                                                image.preview_url
                                                            }
                                                            alt={image.name}
                                                            className="size-16 object-contain"
                                                        />
                                                    </button>
                                                ),
                                            )}
                                        </div>
                                    )}
                                </>
                            ) : (
                                <div className="flex aspect-square items-center justify-center rounded-xl border bg-muted/30">
                                    <p className="text-sm text-muted-foreground">
                                        No images available
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Product Info */}
                        <div className="space-y-6">
                            <div className="space-y-2">
                                <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                                    {product.name}
                                </h1>
                                {product.brand && (
                                    <p className="text-muted-foreground">
                                        by {product.brand.name}
                                    </p>
                                )}
                            </div>

                            {/* Seal Status Card */}
                            <div className="flex items-center gap-4 rounded-xl border p-4">
                                <img
                                    src={sealImages[product.seal_status]}
                                    alt={sealLabels[product.seal_status]}
                                    className="size-16 shrink-0"
                                />
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">
                                        Safety Seal Status
                                    </p>
                                    <p className="text-lg font-semibold">
                                        {sealLabels[product.seal_status]}
                                    </p>
                                </div>
                            </div>

                            {/* Product Details Grid */}
                            <div className="rounded-xl border p-4">
                                <h2 className="mb-3 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                                    Product Information
                                </h2>
                                <dl className="grid grid-cols-2 gap-x-4 gap-y-3">
                                    <DetailItem
                                        label="EAN"
                                        value={product.ean}
                                        mono
                                    />
                                    <DetailItem
                                        label="Article No."
                                        value={
                                            product.internal_article_number
                                        }
                                    />
                                    <DetailItem
                                        label="Supplier"
                                        value={product.supplier?.name}
                                    />
                                    <DetailItem
                                        label="Category"
                                        value={product.category?.name}
                                    />
                                    {product.order_number && (
                                        <DetailItem
                                            label="Order No."
                                            value={product.order_number}
                                        />
                                    )}
                                    {product.supplier_article_number && (
                                        <DetailItem
                                            label="Supplier Article No."
                                            value={
                                                product.supplier_article_number
                                            }
                                        />
                                    )}
                                </dl>
                            </div>
                        </div>
                    </div>

                    {/* Safety Information */}
                    {hasSafetyData && product.safety_entry && (
                        <section className="mt-10">
                            <h2 className="mb-4 flex items-center gap-2 text-xl font-semibold">
                                <AlertTriangle className="size-5 text-amber-500" />
                                Safety Information
                            </h2>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <SafetyCard
                                    label="Warnings"
                                    value={product.safety_entry.warning_text}
                                />
                                <SafetyCard
                                    label="Age Grading"
                                    value={product.safety_entry.age_grading}
                                />
                                <SafetyCard
                                    label="Materials"
                                    value={
                                        product.safety_entry
                                            .material_information
                                    }
                                />
                                <SafetyCard
                                    label="Usage Restrictions"
                                    value={
                                        product.safety_entry
                                            .usage_restrictions
                                    }
                                />
                                <SafetyCard
                                    label="Safety Instructions"
                                    value={
                                        product.safety_entry
                                            .safety_instructions
                                    }
                                />
                                <SafetyCard
                                    label="General Safety"
                                    value={product.safety_entry.safety_text}
                                />
                                {product.safety_entry.additional_notes && (
                                    <SafetyCard
                                        label="Additional Notes"
                                        value={
                                            product.safety_entry
                                                .additional_notes
                                        }
                                        className="sm:col-span-2"
                                    />
                                )}
                            </div>
                        </section>
                    )}

                    {/* Documents */}
                    {product.documents.length > 0 && (
                        <section className="mt-10">
                            <h2 className="mb-4 flex items-center gap-2 text-xl font-semibold">
                                <FileText className="size-5 text-primary" />
                                Documents
                            </h2>
                            <div className="divide-y rounded-xl border">
                                {product.documents.map((doc) => (
                                    <div
                                        key={doc.id}
                                        className="flex items-center justify-between gap-4 p-4"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">
                                                {doc.type_label}
                                            </p>
                                            {doc.file_name && (
                                                <p className="truncate text-xs text-muted-foreground">
                                                    {doc.file_name}
                                                    {doc.file_size
                                                        ? ` · ${formatFileSize(doc.file_size)}`
                                                        : ''}
                                                </p>
                                            )}
                                        </div>
                                        {doc.file_url && (
                                            <a
                                                href={doc.file_url}
                                                download
                                                className="inline-flex shrink-0 items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors hover:bg-accent"
                                            >
                                                <Download className="size-4" />
                                                Download
                                            </a>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}

                    {/* Trust Indicators */}
                    <section className="mt-10">
                        <h2 className="mb-4 text-xl font-semibold">
                            Compliance Timeline
                        </h2>
                        <div className="relative space-y-0 pl-6">
                            <div className="absolute top-2 bottom-2 left-2.5 w-px bg-border" />
                            <TimelineItem
                                label="Product registered"
                                date={product.created_at}
                                active
                            />
                            {product.supplier && (
                                <TimelineItem
                                    label={`Supplier confirmed: ${product.supplier.name}`}
                                    active
                                />
                            )}
                            {product.documents.length > 0 && (
                                <TimelineItem
                                    label={`${product.documents.length} document${product.documents.length !== 1 ? 's' : ''} available`}
                                    active
                                />
                            )}
                            <TimelineItem
                                label={`Seal status: ${sealLabels[product.seal_status]}`}
                                active={
                                    product.seal_status === 'verified'
                                }
                            />
                        </div>
                    </section>
                </main>

                {/* Footer */}
                <footer className="mt-12 border-t bg-card py-6">
                    <div className="mx-auto max-w-5xl px-4 text-center text-xs text-muted-foreground sm:px-6">
                        <p>
                            This product safety profile is provided for
                            informational purposes. Data is maintained by the
                            product manufacturer/importer.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}

function DetailItem({
    label,
    value,
    mono = false,
}: {
    label: string;
    value?: string | null;
    mono?: boolean;
}) {
    if (!value) {
        return null;
    }

    return (
        <div>
            <dt className="text-xs text-muted-foreground">{label}</dt>
            <dd
                className={cn(
                    'mt-0.5 text-sm font-medium',
                    mono && 'font-mono',
                )}
            >
                {value}
            </dd>
        </div>
    );
}

function SafetyCard({
    label,
    value,
    className,
}: {
    label: string;
    value: string | null;
    className?: string;
}) {
    if (!value) {
        return null;
    }

    return (
        <div className={cn('rounded-xl border p-4', className)}>
            <h3 className="mb-1 text-sm font-semibold text-muted-foreground">
                {label}
            </h3>
            <p className="whitespace-pre-line text-sm">{value}</p>
        </div>
    );
}

function TimelineItem({
    label,
    date,
    active = false,
}: {
    label: string;
    date?: string | null;
    active?: boolean;
}) {
    return (
        <div className="relative flex items-start gap-3 py-2">
            <span
                className={cn(
                    'relative z-10 mt-1.5 size-2 shrink-0 rounded-full',
                    active ? 'bg-green-500' : 'bg-muted-foreground/40',
                )}
            />
            <div>
                <p className="text-sm font-medium">{label}</p>
                {date && (
                    <p className="text-xs text-muted-foreground">
                        {new Date(date).toLocaleDateString(undefined, {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                        })}
                    </p>
                )}
            </div>
        </div>
    );
}
