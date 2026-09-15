import { Form, Head, Link, setLayoutProps, usePage } from '@inertiajs/react';
import {
    CalendarCheck,
    MapPin,
    Pencil,
    ReceiptText,
    Trash2,
    Truck,
} from 'lucide-react';
import { AssetMaintenanceHistory } from '@/components/asset-maintenance-history';
import { AssetStatusBadge } from '@/components/asset-status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import {
    formatAssetCategory,
    formatAssetCurrency,
    formatAssetDate,
} from '@/lib/assets';
import { dashboard } from '@/routes';
import {
    destroy as destroyAsset,
    edit as editAsset,
    index as assetsIndex,
    show as showAsset,
} from '@/routes/assets';
import { edit as editMaintenance } from '@/routes/assets/maintenance';
import type {
    AssetDetail,
    AssetMaintenanceHistoryEntry,
    BreadcrumbItem,
} from '@/types';

type Props = {
    asset: AssetDetail;
    maintenanceHistory: AssetMaintenanceHistoryEntry[];
};

function DetailItem({
    label,
    value,
}: {
    label: string;
    value: React.ReactNode;
}) {
    return (
        <div>
            <dt className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </dt>
            <dd className="mt-1 text-sm font-medium">{value || '—'}</dd>
        </div>
    );
}

export default function ShowAsset({ asset, maintenanceHistory }: Props) {
    const { canManageAssets } = usePage().props.auth;
    // Der Titel in der persistenten Navigation folgt dem aktuell geöffneten Asset.
    setLayoutProps<{ breadcrumbs: BreadcrumbItem[] }>({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Assets', href: assetsIndex() },
            { title: asset.inventoryNumber, href: showAsset(asset) },
        ],
    });

    return (
        <>
            <Head title={asset.name} />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div>
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-3xl font-semibold tracking-tight">
                                {asset.name}
                            </h1>
                            <AssetStatusBadge status={asset.status} />
                        </div>
                        <p className="mt-2 font-mono text-sm text-muted-foreground">
                            {asset.inventoryNumber}
                        </p>
                    </div>
                    {canManageAssets && (
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" asChild>
                                <Link href={editMaintenance(asset)}>
                                    <CalendarCheck />
                                    Wartung erfassen
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href={editAsset(asset)}>
                                    <Pencil />
                                    Bearbeiten
                                </Link>
                            </Button>
                            <Dialog>
                                <DialogTrigger asChild>
                                    <Button
                                        variant="outline"
                                        className="text-destructive hover:text-destructive"
                                    >
                                        <Trash2 />
                                        Löschen
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>
                                            Asset endgültig löschen?
                                        </DialogTitle>
                                        <DialogDescription>
                                            {asset.inventoryNumber} –{' '}
                                            {asset.name} wird unwiderruflich
                                            entfernt. Dieser Vorgang kann nicht
                                            rückgängig gemacht werden.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <Form
                                        {...destroyAsset.form(asset)}
                                        disableWhileProcessing
                                    >
                                        {({ processing }) => (
                                            <DialogFooter>
                                                <DialogClose asChild>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                    >
                                                        Abbrechen
                                                    </Button>
                                                </DialogClose>
                                                <Button
                                                    type="submit"
                                                    variant="destructive"
                                                    disabled={processing}
                                                >
                                                    {processing && <Spinner />}
                                                    Asset löschen
                                                </Button>
                                            </DialogFooter>
                                        )}
                                    </Form>
                                </DialogContent>
                            </Dialog>
                        </div>
                    )}
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <ReceiptText className="size-5 text-muted-foreground" />
                                Stammdaten
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-5 sm:grid-cols-2">
                                <DetailItem
                                    label="Asset-Nummer"
                                    value={asset.inventoryNumber}
                                />
                                <DetailItem
                                    label="Seriennummer"
                                    value={asset.serialNumber}
                                />
                                <DetailItem
                                    label="Kategorie"
                                    value={formatAssetCategory(asset.category)}
                                />
                                <DetailItem
                                    label="Status"
                                    value={
                                        <AssetStatusBadge
                                            status={asset.status}
                                        />
                                    }
                                />
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MapPin className="size-5 text-muted-foreground" />
                                Standort
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-5 sm:grid-cols-3">
                                <DetailItem
                                    label="Standort"
                                    value={asset.location.site}
                                />
                                <DetailItem
                                    label="Gebäude"
                                    value={asset.location.building}
                                />
                                <DetailItem
                                    label="Raum"
                                    value={asset.location.room}
                                />
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Truck className="size-5 text-muted-foreground" />
                                Lieferant &amp; Anschaffung
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-5 sm:grid-cols-2">
                                <DetailItem
                                    label="Lieferant"
                                    value={asset.supplier?.name}
                                />
                                <DetailItem
                                    label="Externe ID"
                                    value={asset.supplier?.externalId}
                                />
                                <DetailItem
                                    label="Anschaffungsdatum"
                                    value={formatAssetDate(
                                        asset.acquisitionDate,
                                    )}
                                />
                                <DetailItem
                                    label="Anschaffungswert"
                                    value={formatAssetCurrency(
                                        asset.acquisitionValue,
                                        asset.currency,
                                    )}
                                />
                                <DetailItem
                                    label="Garantie bis"
                                    value={formatAssetDate(asset.warrantyUntil)}
                                />
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CalendarCheck className="size-5 text-muted-foreground" />
                                Wartung
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-5 sm:grid-cols-2">
                                <DetailItem
                                    label="Letzte Wartung"
                                    value={formatAssetDate(
                                        asset.maintenance?.lastCompletedAt ??
                                            null,
                                    )}
                                />
                                <DetailItem
                                    label="Nächste Wartung"
                                    value={formatAssetDate(
                                        asset.maintenance?.nextDueAt ?? null,
                                    )}
                                />
                                <DetailItem
                                    label="Intervall"
                                    value={
                                        asset.maintenance
                                            ? `${asset.maintenance.intervalDays} Tage`
                                            : null
                                    }
                                />
                                <DetailItem
                                    label="Letzte Notiz"
                                    value={asset.maintenance?.note}
                                />
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Wartungshistorie</CardTitle>
                        <CardDescription>
                            Chronologisches Protokoll aller erfassten Wartungen.
                            Bestehende Einträge werden nicht überschrieben.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <AssetMaintenanceHistory entries={maintenanceHistory} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
