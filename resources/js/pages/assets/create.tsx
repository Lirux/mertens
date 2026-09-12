import { Head } from '@inertiajs/react';
import { AssetForm } from '@/components/asset-form';
import { dashboard } from '@/routes';
import {
    create as createAsset,
    index as assetsIndex,
    store as storeAsset,
} from '@/routes/assets';

export default function CreateAsset({ categories }: { categories: string[] }) {
    return (
        <>
            <Head title="Asset erfassen" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        Asset erfassen
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Alle Pflichtfelder sind mit * gekennzeichnet.
                    </p>
                </div>
                <AssetForm
                    action={storeAsset.form()}
                    categories={categories}
                    submitLabel="Asset speichern"
                />
            </div>
        </>
    );
}

CreateAsset.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Assets', href: assetsIndex() },
        { title: 'Neu', href: createAsset() },
    ],
};
