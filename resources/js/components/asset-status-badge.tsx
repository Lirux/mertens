import { Badge } from '@/components/ui/badge';
import { assetStatusLabels } from '@/lib/assets';
import { cn } from '@/lib/utils';
import type { AssetStatus } from '@/types';

const statusClasses: Record<AssetStatus, string> = {
    active: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300',
    maintenance:
        'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
    inactive:
        'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    retired:
        'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300',
};

export function AssetStatusBadge({
    status,
    className,
}: {
    status: AssetStatus;
    className?: string;
}) {
    return (
        <Badge
            variant="outline"
            className={cn(statusClasses[status], className)}
        >
            {assetStatusLabels[status]}
        </Badge>
    );
}
