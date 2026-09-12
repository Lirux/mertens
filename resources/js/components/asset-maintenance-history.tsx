import { History } from 'lucide-react';
import { AssetStatusBadge } from '@/components/asset-status-badge';
import { formatAssetDate, formatAssetDateTime } from '@/lib/assets';
import type { AssetMaintenanceHistoryEntry } from '@/types';

type Props = {
    entries: AssetMaintenanceHistoryEntry[];
    emptyMessage?: string;
};

export function AssetMaintenanceHistory({
    entries,
    emptyMessage = 'Für dieses Asset wurde noch keine Wartung protokolliert.',
}: Props) {
    if (entries.length === 0) {
        return (
            <div className="rounded-xl border border-dashed p-8 text-center">
                <History className="mx-auto size-8 text-muted-foreground" />
                <p className="mt-3 text-sm text-muted-foreground">
                    {emptyMessage}
                </p>
            </div>
        );
    }

    return (
        <>
            <div className="hidden overflow-x-auto lg:block">
                <table
                    className="w-full text-left text-sm"
                    aria-label="Wartungshistorie"
                >
                    <thead className="border-b text-xs font-medium text-muted-foreground uppercase">
                        <tr>
                            <th className="px-3 py-3">Wartungsdatum</th>
                            <th className="px-3 py-3">Status</th>
                            <th className="px-3 py-3">Intervall</th>
                            <th className="px-3 py-3">Notiz</th>
                            <th className="px-3 py-3">Protokolliert</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {entries.map((entry) => (
                            <tr key={entry.id}>
                                <td className="px-3 py-4 align-top font-medium tabular-nums">
                                    {formatAssetDate(entry.completedAt)}
                                </td>
                                <td className="px-3 py-4 align-top">
                                    <AssetStatusBadge
                                        status={entry.statusAfter}
                                    />
                                </td>
                                <td className="px-3 py-4 align-top">
                                    <span className="font-medium">
                                        {entry.intervalDays} Tage
                                    </span>
                                    <span className="mt-1 block text-xs text-muted-foreground">
                                        Folgetermin{' '}
                                        {formatAssetDate(entry.nextDueAt)}
                                    </span>
                                </td>
                                <td className="max-w-xs px-3 py-4 align-top text-muted-foreground">
                                    {entry.note || '—'}
                                </td>
                                <td className="px-3 py-4 align-top">
                                    <span className="font-medium">
                                        {entry.recordedBy.name}
                                    </span>
                                    <span className="mt-1 block text-xs text-muted-foreground">
                                        {formatAssetDateTime(entry.recordedAt)}
                                    </span>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="grid gap-3 lg:hidden">
                {entries.map((entry) => (
                    <article
                        key={entry.id}
                        className="rounded-xl border bg-card p-4 shadow-xs"
                    >
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    Wartung
                                </p>
                                <p className="mt-1 font-semibold tabular-nums">
                                    {formatAssetDate(entry.completedAt)}
                                </p>
                            </div>
                            <AssetStatusBadge status={entry.statusAfter} />
                        </div>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="text-xs font-medium text-muted-foreground">
                                    Intervall &amp; Folgetermin
                                </dt>
                                <dd className="mt-1 text-sm">
                                    {entry.intervalDays} Tage ·{' '}
                                    {formatAssetDate(entry.nextDueAt)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs font-medium text-muted-foreground">
                                    Protokolliert von
                                </dt>
                                <dd className="mt-1 text-sm">
                                    {entry.recordedBy.name}
                                    <span className="block text-xs text-muted-foreground">
                                        {formatAssetDateTime(entry.recordedAt)}
                                    </span>
                                </dd>
                            </div>
                            <div className="sm:col-span-2">
                                <dt className="text-xs font-medium text-muted-foreground">
                                    Notiz
                                </dt>
                                <dd className="mt-1 text-sm text-muted-foreground">
                                    {entry.note || '—'}
                                </dd>
                            </div>
                        </dl>
                    </article>
                ))}
            </div>
        </>
    );
}
