import { cn } from '@/lib/utils';
import type { SealStatus } from '@/types';

type Props = {
    score: number;
    sealStatus: SealStatus;
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

function scoreColor(score: number): string {
    if (score >= 80) {
        return 'text-green-600 dark:text-green-400';
    }

    if (score >= 50) {
        return 'text-amber-500 dark:text-amber-400';
    }

    return 'text-red-500 dark:text-red-400';
}

function barColor(score: number): string {
    if (score >= 80) {
        return 'bg-green-500';
    }

    if (score >= 50) {
        return 'bg-amber-500';
    }

    return 'bg-red-500';
}

export default function ProductCompleteness({ score, sealStatus }: Props) {
    const rounded = Math.round(score);

    return (
        <div className="flex items-center gap-6 rounded-xl border p-6">
            <img
                src={sealImages[sealStatus]}
                alt={sealLabels[sealStatus]}
                className="size-20 shrink-0"
            />

            <div className="flex min-w-0 flex-1 flex-col gap-2">
                <div className="flex items-baseline justify-between gap-2">
                    <h3 className="text-sm font-medium text-muted-foreground">
                        Completeness Score
                    </h3>
                    <span
                        className={cn(
                            'text-lg font-semibold tabular-nums',
                            scoreColor(score),
                        )}
                    >
                        {rounded}%
                    </span>
                </div>

                <div className="relative h-2.5 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        className={cn(
                            'h-full rounded-full transition-all duration-500',
                            barColor(score),
                        )}
                        style={{ width: `${rounded}%` }}
                    />
                </div>

                <p className="text-xs text-muted-foreground">
                    Swiss Product Seal:{' '}
                    <span className="font-medium text-foreground">
                        {sealLabels[sealStatus]}
                    </span>
                </p>
            </div>
        </div>
    );
}
