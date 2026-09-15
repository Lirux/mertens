import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Boxes, CalendarClock, CircleOff } from 'lucide-react';
import { AssetStatusBadge } from '@/components/asset-status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatAssetDate } from '@/lib/assets';
import { dashboard } from '@/routes';
import {
    create as createAsset,
    index as assetsIndex,
    show as showAsset,
} from '@/routes/assets';
import type { AssetSummary, DashboardStats } from '@/types';

type Props = {
    stats: DashboardStats;
    upcomingMaintenance: AssetSummary[];
};

export default function Dashboard({ stats, upcomingMaintenance }: Props) {
    const { canManageAssets } = usePage().props.auth;

    return (
        <>
            <Head title="Dashboard" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div>
                    <p className="text-sm font-medium text-muted-foreground">
                        Asset Management
                    </p>
                    <h1 className="mt-1 text-3xl font-semibold tracking-tight">
                        Dashboard
                    </h1>
                    <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
                        Anlagenbestand, Wartungstermine und kritische Zustände
                        auf einen Blick.
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex-row items-start justify-between gap-4">
                            <div className="grid gap-1.5">
                                <CardDescription>Assets gesamt</CardDescription>
                                <CardTitle className="text-3xl">
                                    {stats.total}
                                </CardTitle>
                            </div>
                            <div className="rounded-lg bg-primary/10 p-2 text-primary">
                                <Boxes className="size-5" />
                            </div>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">
                            +{stats.createdThisMonth} in diesem Monat
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex-row items-start justify-between gap-4">
                            <div className="grid gap-1.5">
                                <CardDescription>
                                    Wartung fällig
                                </CardDescription>
                                <CardTitle className="text-3xl">
                                    {stats.maintenanceDue}
                                </CardTitle>
                            </div>
                            <div className="rounded-lg bg-amber-100 p-2 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                <CalendarClock className="size-5" />
                            </div>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">
                            {stats.overdue} davon überfällig
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex-row items-start justify-between gap-4">
                            <div className="grid gap-1.5">
                                <CardDescription>
                                    Ausser Betrieb
                                </CardDescription>
                                <CardTitle className="text-3xl">
                                    {stats.inactive}
                                </CardTitle>
                            </div>
                            <div className="rounded-lg bg-red-100 p-2 text-red-700 dark:bg-red-950 dark:text-red-300">
                                <CircleOff className="size-5" />
                            </div>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">
                            Prüfung oder Entscheidung erforderlich
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="flex-row items-center justify-between gap-4">
                        <div>
                            <CardTitle>Nächste Wartungen</CardTitle>
                            <CardDescription>
                                Die frühesten anstehenden Termine
                            </CardDescription>
                        </div>
                        <Button variant="ghost" size="sm" asChild>
                            <Link
                                href={assetsIndex({
                                    query: {
                                        maintenanceDue: 'next_30_days',
                                    },
                                })}
                            >
                                Alle anzeigen
                                <ArrowRight />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {upcomingMaintenance.length === 0 ? (
                            <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                Es sind noch keine Wartungstermine hinterlegt.
                            </div>
                        ) : (
                            <div className="divide-y">
                                {upcomingMaintenance.map((asset) => (
                                    <Link
                                        key={asset.id}
                                        href={showAsset(asset)}
                                        className="grid gap-2 py-4 transition-colors first:pt-0 last:pb-0 hover:text-primary sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
                                    >
                                        <div className="min-w-0">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="font-medium">
                                                    {asset.name}
                                                </span>
                                                <AssetStatusBadge
                                                    status={asset.status}
                                                />
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {asset.inventoryNumber} ·{' '}
                                                {asset.location.site},{' '}
                                                {asset.location.building}
                                            </p>
                                        </div>
                                        <span className="text-sm font-medium tabular-nums">
                                            {formatAssetDate(
                                                asset.maintenance?.nextDueAt ??
                                                    null,
                                            )}
                                        </span>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="flex flex-col gap-3 sm:flex-row">
                    <Button asChild>
                        <Link href={assetsIndex()}>Assets anzeigen</Link>
                    </Button>
                    {canManageAssets && (
                        <Button variant="outline" asChild>
                            <Link href={createAsset()}>Asset erfassen</Link>
                        </Button>
                    )}
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
