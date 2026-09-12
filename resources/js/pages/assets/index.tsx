import { Form, Head, Link } from '@inertiajs/react';
import { Filter, Plus, Search, X } from 'lucide-react';
import { AssetStatusBadge } from '@/components/asset-status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    assetStatusLabels,
    formatAssetCategory,
    formatAssetDate,
} from '@/lib/assets';
import { dashboard } from '@/routes';
import {
    create as createAsset,
    index as assetsIndex,
    show as showAsset,
} from '@/routes/assets';
import type { AssetFilters, AssetStatus, PaginatedAssets } from '@/types';

type Props = {
    assets: PaginatedAssets;
    filters: AssetFilters;
    categories: string[];
};

const statusOptions = Object.entries(assetStatusLabels) as [
    AssetStatus,
    string,
][];

const selectClassName =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30';

export default function AssetsIndex({ assets, filters, categories }: Props) {
    const hasFilters = Object.values(filters).some(Boolean);

    return (
        <>
            <Head title="Assets" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-medium text-muted-foreground">
                            Bestand durchsuchen und verwalten
                        </p>
                        <h1 className="mt-1 text-3xl font-semibold tracking-tight">
                            Assets
                        </h1>
                    </div>
                    <Button asChild>
                        <Link href={createAsset()}>
                            <Plus />
                            Asset erfassen
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Assets suchen &amp; filtern</CardTitle>
                        <CardDescription>
                            Suche nach Nummer, Name oder Seriennummer.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...assetsIndex.form()}
                            className="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                        >
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="search">Suche</Label>
                                <div className="relative">
                                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        name="search"
                                        defaultValue={filters.search ?? ''}
                                        className="pl-9"
                                        placeholder="Nummer, Name oder Seriennummer"
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    name="status"
                                    defaultValue={filters.status ?? ''}
                                    className={selectClassName}
                                >
                                    <option value="">Alle Status</option>
                                    {statusOptions.map(([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="category">Kategorie</Label>
                                <select
                                    id="category"
                                    name="category"
                                    defaultValue={filters.category ?? ''}
                                    className={selectClassName}
                                >
                                    <option value="">Alle Kategorien</option>
                                    {categories.map((category) => (
                                        <option key={category} value={category}>
                                            {formatAssetCategory(category)}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="maintenanceDue">
                                    Wartungsfälligkeit
                                </Label>
                                <select
                                    id="maintenanceDue"
                                    name="maintenanceDue"
                                    defaultValue={filters.maintenanceDue ?? ''}
                                    className={selectClassName}
                                >
                                    <option value="">Alle Termine</option>
                                    <option value="next_30_days">
                                        Nächste 30 Tage
                                    </option>
                                    <option value="overdue">Überfällig</option>
                                </select>
                            </div>
                            <div className="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-3">
                                <Button type="submit">
                                    <Filter />
                                    Filter anwenden
                                </Button>
                                {hasFilters && (
                                    <Button variant="outline" asChild>
                                        <Link href={assetsIndex()}>
                                            <X />
                                            Zurücksetzen
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            {assets.total}{' '}
                            {assets.total === 1 ? 'Treffer' : 'Treffer'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {assets.data.length === 0 ? (
                            <div className="rounded-xl border border-dashed p-10 text-center">
                                <h2 className="font-semibold">
                                    Keine Assets gefunden
                                </h2>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    Filter anpassen oder Suchbegriff entfernen.
                                </p>
                                {hasFilters && (
                                    <Button
                                        variant="outline"
                                        className="mt-5"
                                        asChild
                                    >
                                        <Link href={assetsIndex()}>
                                            Filter zurücksetzen
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <>
                                <div className="hidden overflow-x-auto md:block">
                                    <table className="w-full text-left text-sm">
                                        <thead className="border-b text-xs font-medium text-muted-foreground uppercase">
                                            <tr>
                                                <th className="px-3 py-3">
                                                    Asset-Nr.
                                                </th>
                                                <th className="px-3 py-3">
                                                    Name
                                                </th>
                                                <th className="px-3 py-3">
                                                    Kategorie
                                                </th>
                                                <th className="px-3 py-3">
                                                    Standort
                                                </th>
                                                <th className="px-3 py-3">
                                                    Nächste Wartung
                                                </th>
                                                <th className="px-3 py-3">
                                                    Status
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {assets.data.map((asset) => (
                                                <tr
                                                    key={asset.id}
                                                    className="transition-colors hover:bg-muted/50"
                                                >
                                                    <td className="px-3 py-4 font-mono text-xs">
                                                        {asset.inventoryNumber}
                                                    </td>
                                                    <td className="px-3 py-4 font-medium">
                                                        <Link
                                                            href={showAsset(
                                                                asset,
                                                            )}
                                                            className="hover:underline"
                                                        >
                                                            {asset.name}
                                                        </Link>
                                                    </td>
                                                    <td className="px-3 py-4 text-muted-foreground">
                                                        {formatAssetCategory(
                                                            asset.category,
                                                        )}
                                                    </td>
                                                    <td className="px-3 py-4 text-muted-foreground">
                                                        {asset.location.site} ·{' '}
                                                        {
                                                            asset.location
                                                                .building
                                                        }
                                                    </td>
                                                    <td className="px-3 py-4 tabular-nums">
                                                        {formatAssetDate(
                                                            asset.maintenance
                                                                ?.nextDueAt ??
                                                                null,
                                                        )}
                                                    </td>
                                                    <td className="px-3 py-4">
                                                        <AssetStatusBadge
                                                            status={
                                                                asset.status
                                                            }
                                                        />
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="grid gap-3 md:hidden">
                                    {assets.data.map((asset) => (
                                        <Link
                                            key={asset.id}
                                            href={showAsset(asset)}
                                            className="rounded-xl border p-4 transition-colors hover:bg-muted/50"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="min-w-0">
                                                    <p className="truncate font-semibold">
                                                        {asset.name}
                                                    </p>
                                                    <p className="mt-1 font-mono text-xs text-muted-foreground">
                                                        {asset.inventoryNumber}
                                                    </p>
                                                </div>
                                                <AssetStatusBadge
                                                    status={asset.status}
                                                />
                                            </div>
                                            <p className="mt-3 text-sm text-muted-foreground">
                                                {formatAssetCategory(
                                                    asset.category,
                                                )}{' '}
                                                · {asset.location.site},{' '}
                                                {asset.location.building}
                                            </p>
                                            <p className="mt-2 text-sm">
                                                Nächste Wartung:{' '}
                                                {formatAssetDate(
                                                    asset.maintenance
                                                        ?.nextDueAt ?? null,
                                                )}
                                            </p>
                                        </Link>
                                    ))}
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>

                {assets.last_page > 1 && (
                    <div className="flex flex-col items-center justify-between gap-3 text-sm sm:flex-row">
                        <p className="text-muted-foreground">
                            {assets.from}–{assets.to} von {assets.total}
                        </p>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!assets.prev_page_url}
                                asChild={Boolean(assets.prev_page_url)}
                            >
                                {assets.prev_page_url ? (
                                    <Link href={assets.prev_page_url}>
                                        Zurück
                                    </Link>
                                ) : (
                                    <span>Zurück</span>
                                )}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                disabled={!assets.next_page_url}
                                asChild={Boolean(assets.next_page_url)}
                            >
                                {assets.next_page_url ? (
                                    <Link href={assets.next_page_url}>
                                        Weiter
                                    </Link>
                                ) : (
                                    <span>Weiter</span>
                                )}
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

AssetsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Assets', href: assetsIndex() },
    ],
};
