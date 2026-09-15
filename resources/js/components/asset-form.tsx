import { Form, Link } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { assetStatusLabels } from '@/lib/assets';
import { cn } from '@/lib/utils';
import { index as assetsIndex, show as showAsset } from '@/routes/assets';
import type { AssetDetail, AssetFormValues, AssetStatus } from '@/types';

type AssetFormProps = {
    action: {
        action: string;
        method: 'post';
    };
    asset?: AssetDetail;
    categories: string[];
    submitLabel: string;
};

const statusOptions = Object.entries(assetStatusLabels) as [
    AssetStatus,
    string,
][];

const selectClassName =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30 dark:aria-invalid:ring-destructive/40';

/** Bereitet dasselbe Formular für Neuanlage und Bearbeitung vor; optionale Felder starten leer. */
function valuesFor(asset?: AssetDetail): AssetFormValues {
    return {
        inventoryNumber: asset?.inventoryNumber ?? '',
        serialNumber: asset?.serialNumber ?? '',
        name: asset?.name ?? '',
        category: asset?.category ?? '',
        status: asset?.status ?? 'active',
        location: asset?.location ?? { site: '', building: '', room: '' },
        supplier: asset?.supplier ?? { externalId: '', name: '' },
        acquisitionDate: asset?.acquisitionDate ?? '',
        acquisitionValue: asset?.acquisitionValue ?? '',
        currency: asset?.currency ?? 'CHF',
        warrantyUntil: asset?.warrantyUntil ?? '',
        maintenance: {
            nextDueAt: asset?.maintenance?.nextDueAt ?? '',
            intervalDays: asset?.maintenance?.intervalDays.toString() ?? '',
        },
    };
}

function FormField({
    id,
    label,
    error,
    required = false,
    className,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    required?: boolean;
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={id}>
                {label}
                {required && <span aria-hidden="true"> *</span>}
            </Label>
            {children}
            <InputError message={error} />
        </div>
    );
}

/** Inertia übermittelt die benannten Felder; fachliche Validierungsfehler kommen vom Server zurück. */
export function AssetForm({
    action,
    asset,
    categories,
    submitLabel,
}: AssetFormProps) {
    const values = valuesFor(asset);
    const categoryOptions = Array.from(
        new Set([...categories, values.category].filter(Boolean)),
    );

    return (
        <Form {...action} disableWhileProcessing className="grid gap-6">
            {({ errors, processing }) => (
                <>
                    <Card>
                        <CardHeader>
                            <CardTitle>Stammdaten</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            <FormField
                                id="inventoryNumber"
                                label="Asset-Nummer"
                                error={errors.inventoryNumber}
                                required
                            >
                                <Input
                                    id="inventoryNumber"
                                    name="inventoryNumber"
                                    defaultValue={values.inventoryNumber}
                                    maxLength={50}
                                    required
                                    autoFocus
                                    aria-invalid={Boolean(
                                        errors.inventoryNumber,
                                    )}
                                    placeholder="CH-ZH-2026-0042"
                                />
                            </FormField>
                            <FormField
                                id="serialNumber"
                                label="Seriennummer"
                                error={errors.serialNumber}
                            >
                                <Input
                                    id="serialNumber"
                                    name="serialNumber"
                                    defaultValue={values.serialNumber}
                                    maxLength={100}
                                    aria-invalid={Boolean(errors.serialNumber)}
                                    placeholder="HPU4-26-1048"
                                />
                            </FormField>
                            <FormField
                                id="name"
                                label="Name"
                                error={errors.name}
                                required
                            >
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={values.name}
                                    maxLength={150}
                                    required
                                    aria-invalid={Boolean(errors.name)}
                                    placeholder="Hydraulikaggregat HPU-400"
                                />
                            </FormField>
                            <FormField
                                id="category"
                                label="Kategorie"
                                error={errors.category}
                                required
                            >
                                <Input
                                    id="category"
                                    name="category"
                                    list="asset-categories"
                                    defaultValue={values.category}
                                    maxLength={100}
                                    required
                                    aria-invalid={Boolean(errors.category)}
                                    placeholder="Hydrauliksystem"
                                />
                                <datalist id="asset-categories">
                                    {categoryOptions.map((category) => (
                                        <option
                                            key={category}
                                            value={category}
                                        />
                                    ))}
                                </datalist>
                            </FormField>
                            <FormField
                                id="status"
                                label="Status"
                                error={errors.status}
                                required
                            >
                                <select
                                    id="status"
                                    name="status"
                                    defaultValue={values.status}
                                    className={selectClassName}
                                    required
                                    aria-invalid={Boolean(errors.status)}
                                >
                                    {statusOptions.map(([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                            </FormField>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Standort &amp; Lieferant</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            <FormField
                                id="location.site"
                                label="Standort"
                                error={errors['location.site']}
                                required
                            >
                                <Input
                                    id="location.site"
                                    name="location.site"
                                    defaultValue={values.location.site}
                                    required
                                    aria-invalid={Boolean(
                                        errors['location.site'],
                                    )}
                                    placeholder="Zürich"
                                />
                            </FormField>
                            <FormField
                                id="location.building"
                                label="Gebäude"
                                error={errors['location.building']}
                                required
                            >
                                <Input
                                    id="location.building"
                                    name="location.building"
                                    defaultValue={values.location.building}
                                    required
                                    aria-invalid={Boolean(
                                        errors['location.building'],
                                    )}
                                    placeholder="Werkhalle 2"
                                />
                            </FormField>
                            <FormField
                                id="location.room"
                                label="Raum"
                                error={errors['location.room']}
                                required
                            >
                                <Input
                                    id="location.room"
                                    name="location.room"
                                    defaultValue={values.location.room}
                                    required
                                    aria-invalid={Boolean(
                                        errors['location.room'],
                                    )}
                                    placeholder="Hydraulikprüfstand"
                                />
                            </FormField>
                            <FormField
                                id="supplier.name"
                                label="Lieferant"
                                error={errors['supplier.name']}
                            >
                                <Input
                                    id="supplier.name"
                                    name="supplier.name"
                                    defaultValue={values.supplier.name}
                                    aria-invalid={Boolean(
                                        errors['supplier.name'],
                                    )}
                                    placeholder="Bucher Hydraulics"
                                />
                            </FormField>
                            <FormField
                                id="supplier.externalId"
                                label="Externe ID"
                                error={errors['supplier.externalId']}
                            >
                                <Input
                                    id="supplier.externalId"
                                    name="supplier.externalId"
                                    defaultValue={values.supplier.externalId}
                                    aria-invalid={Boolean(
                                        errors['supplier.externalId'],
                                    )}
                                    placeholder="BH-HPU-1048"
                                />
                            </FormField>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Anschaffung &amp; Wartung</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            <FormField
                                id="acquisitionDate"
                                label="Anschaffungsdatum"
                                error={errors.acquisitionDate}
                            >
                                <Input
                                    id="acquisitionDate"
                                    type="date"
                                    name="acquisitionDate"
                                    defaultValue={values.acquisitionDate}
                                    aria-invalid={Boolean(
                                        errors.acquisitionDate,
                                    )}
                                />
                            </FormField>
                            <FormField
                                id="acquisitionValue"
                                label="Anschaffungswert"
                                error={errors.acquisitionValue}
                                required
                            >
                                <Input
                                    id="acquisitionValue"
                                    type="number"
                                    name="acquisitionValue"
                                    defaultValue={values.acquisitionValue}
                                    min="0"
                                    step="0.01"
                                    required
                                    aria-invalid={Boolean(
                                        errors.acquisitionValue,
                                    )}
                                    placeholder="125000.00"
                                />
                            </FormField>
                            <FormField
                                id="currency"
                                label="Währung"
                                error={errors.currency}
                                required
                            >
                                <select
                                    id="currency"
                                    name="currency"
                                    defaultValue={values.currency}
                                    className={selectClassName}
                                    required
                                    aria-invalid={Boolean(errors.currency)}
                                >
                                    <option value="CHF">CHF</option>
                                    <option value="EUR">EUR</option>
                                    <option value="USD">USD</option>
                                </select>
                            </FormField>
                            <FormField
                                id="warrantyUntil"
                                label="Garantie bis"
                                error={errors.warrantyUntil}
                            >
                                <Input
                                    id="warrantyUntil"
                                    type="date"
                                    name="warrantyUntil"
                                    defaultValue={values.warrantyUntil}
                                    aria-invalid={Boolean(errors.warrantyUntil)}
                                />
                            </FormField>
                            <FormField
                                id="maintenance.intervalDays"
                                label="Wartungsintervall (Tage)"
                                error={errors['maintenance.intervalDays']}
                            >
                                <Input
                                    id="maintenance.intervalDays"
                                    type="number"
                                    name="maintenance.intervalDays"
                                    defaultValue={
                                        values.maintenance.intervalDays
                                    }
                                    min="1"
                                    max="3650"
                                    aria-invalid={Boolean(
                                        errors['maintenance.intervalDays'],
                                    )}
                                    placeholder="180"
                                />
                            </FormField>
                            <FormField
                                id="maintenance.nextDueAt"
                                label="Nächste Wartung"
                                error={errors['maintenance.nextDueAt']}
                            >
                                <Input
                                    id="maintenance.nextDueAt"
                                    type="date"
                                    name="maintenance.nextDueAt"
                                    defaultValue={values.maintenance.nextDueAt}
                                    aria-invalid={Boolean(
                                        errors['maintenance.nextDueAt'],
                                    )}
                                />
                            </FormField>
                        </CardContent>
                    </Card>

                    <div className="flex flex-col-reverse gap-3 sm:flex-row">
                        <Button variant="outline" asChild>
                            <Link
                                href={asset ? showAsset(asset) : assetsIndex()}
                            >
                                Abbrechen
                            </Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
