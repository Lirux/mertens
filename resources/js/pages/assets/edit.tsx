import { Head, setLayoutProps } from '@inertiajs/react';
import { AssetForm } from '@/components/asset-form';
import { dashboard } from '@/routes';
import {
    edit as editAsset,
    index as assetsIndex,
    show as showAsset,
    update as updateAsset,
} from '@/routes/assets';
import type { AssetDetail, BreadcrumbItem } from '@/types';

type Props = {
    asset: AssetDetail;
    categories: string[];
};

export default function EditAsset({ asset, categories }: Props) {
    setLayoutProps<{ breadcrumbs: BreadcrumbItem[] }>({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Assets', href: assetsIndex() },
            { title: asset.inventoryNumber, href: showAsset(asset) },
            { title: 'Bearbeiten', href: editAsset(asset) },
        ],
    });

    return (
        <>
            <Head title={`${asset.name} bearbeiten`} />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        Asset bearbeiten
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Vorausgefüllte Werte · letzte Änderung{' '}
                        {asset.updatedAt
                            ? new Intl.DateTimeFormat('de-CH').format(
                                  new Date(asset.updatedAt),
                              )
                            : '—'}
                    </p>
                </div>
                <AssetForm
                    action={updateAsset.form(asset)}
                    asset={asset}
                    categories={categories}
                    submitLabel="Änderungen speichern"
                />
            </div>
        </>
    );
}
