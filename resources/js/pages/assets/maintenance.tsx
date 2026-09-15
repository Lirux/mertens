import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { CalendarCheck } from 'lucide-react';
import { useMemo, useState } from 'react';
import { AssetMaintenanceHistory } from '@/components/asset-maintenance-history';
import { AssetStatusBadge } from '@/components/asset-status-badge';
import InputError from '@/components/input-error';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatAssetDate } from '@/lib/assets';
import { dashboard } from '@/routes';
import { index as assetsIndex, show as showAsset } from '@/routes/assets';
import {
    edit as editMaintenance,
    update as updateMaintenance,
} from '@/routes/assets/maintenance';
import type {
    AssetDetail,
    AssetMaintenanceHistoryEntry,
    BreadcrumbItem,
} from '@/types';

type Props = {
    asset: AssetDetail;
    maintenanceHistory: AssetMaintenanceHistoryEntry[];
};

const selectClassName =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30 dark:aria-invalid:ring-destructive/40';

function isoToday(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

export default function AssetMaintenance({ asset, maintenanceHistory }: Props) {
    const [completedAt, setCompletedAt] = useState(isoToday());
    const [intervalDays, setIntervalDays] = useState(
        String(asset.maintenance?.intervalDays ?? 180),
    );
    // Lokale Vorschau des Folgetermins; beim Speichern berechnet Laravel ihn erneut.
    const nextDueAt = useMemo(() => {
        const interval = Number(intervalDays);

        if (!completedAt || !Number.isInteger(interval) || interval < 1) {
            return null;
        }

        const date = new Date(`${completedAt}T12:00:00`);
        date.setDate(date.getDate() + interval);

        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }, [completedAt, intervalDays]);

    setLayoutProps<{ breadcrumbs: BreadcrumbItem[] }>({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Assets', href: assetsIndex() },
            { title: asset.inventoryNumber, href: showAsset(asset) },
            { title: 'Wartung', href: editMaintenance(asset) },
        ],
    });

    return (
        <>
            <Head title={`Wartung – ${asset.name}`} />
            <div className="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-3xl font-semibold tracking-tight">
                            Wartung erfassen
                        </h1>
                        <AssetStatusBadge status={asset.status} />
                    </div>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {asset.inventoryNumber} · {asset.name}
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Aktueller Wartungsstand</CardTitle>
                        <CardDescription>
                            Letzter Abschluss{' '}
                            {formatAssetDate(
                                asset.maintenance?.lastCompletedAt ?? null,
                            )}{' '}
                            · nächster Termin{' '}
                            {formatAssetDate(
                                asset.maintenance?.nextDueAt ?? null,
                            )}
                        </CardDescription>
                    </CardHeader>
                </Card>

                <Form
                    {...updateMaintenance.form(asset)}
                    disableWhileProcessing
                    className="grid gap-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <Card>
                                <CardHeader>
                                    <CardTitle>Durchgeführte Wartung</CardTitle>
                                    <CardDescription>
                                        Der Folgetermin wird aus Datum und
                                        Intervall serverseitig berechnet.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="grid gap-5 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="lastCompletedAt">
                                            Wartungsdatum *
                                        </Label>
                                        <Input
                                            id="lastCompletedAt"
                                            name="lastCompletedAt"
                                            type="date"
                                            value={completedAt}
                                            onChange={(event) =>
                                                setCompletedAt(
                                                    event.target.value,
                                                )
                                            }
                                            required
                                            aria-invalid={Boolean(
                                                errors.lastCompletedAt,
                                            )}
                                        />
                                        <InputError
                                            message={errors.lastCompletedAt}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="intervalDays">
                                            Wartungsintervall (Tage) *
                                        </Label>
                                        <Input
                                            id="intervalDays"
                                            name="intervalDays"
                                            type="number"
                                            value={intervalDays}
                                            onChange={(event) =>
                                                setIntervalDays(
                                                    event.target.value,
                                                )
                                            }
                                            min="1"
                                            max="3650"
                                            required
                                            aria-invalid={Boolean(
                                                errors.intervalDays,
                                            )}
                                        />
                                        <InputError
                                            message={errors.intervalDays}
                                        />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label>Berechneter Folgetermin</Label>
                                        <div className="flex min-h-9 items-center gap-2 rounded-md border bg-muted/40 px-3 text-sm font-medium">
                                            <CalendarCheck className="size-4 text-muted-foreground" />
                                            {nextDueAt
                                                ? formatAssetDate(nextDueAt)
                                                : 'Datum und gültiges Intervall eingeben'}
                                        </div>
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="statusAfter">
                                            Zustand nach der Wartung *
                                        </Label>
                                        <select
                                            id="statusAfter"
                                            name="statusAfter"
                                            defaultValue="active"
                                            className={selectClassName}
                                            required
                                            aria-invalid={Boolean(
                                                errors.statusAfter,
                                            )}
                                        >
                                            <option value="active">
                                                Abgeschlossen – Asset aktiv
                                            </option>
                                            <option value="maintenance">
                                                Weiterhin in Wartung
                                            </option>
                                        </select>
                                        <InputError
                                            message={errors.statusAfter}
                                        />
                                    </div>
                                    <div className="grid gap-2 md:col-span-2">
                                        <Label htmlFor="note">
                                            Wartungsnotiz
                                        </Label>
                                        <Textarea
                                            id="note"
                                            name="note"
                                            defaultValue=""
                                            maxLength={1000}
                                            rows={5}
                                            aria-invalid={Boolean(errors.note)}
                                            placeholder="Durchgeführte Arbeiten und Beobachtungen"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Die Notiz wird mit dieser Wartung in
                                            der Historie gespeichert und nicht
                                            nachträglich überschrieben.
                                        </p>
                                        <InputError message={errors.note} />
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="flex flex-col-reverse gap-3 sm:flex-row">
                                <Button variant="outline" asChild>
                                    <Link href={showAsset(asset)}>
                                        Abbrechen
                                    </Link>
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Wartung speichern
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <Card>
                    <CardHeader>
                        <CardTitle>Letzte Wartungen</CardTitle>
                        <CardDescription>
                            Die drei zuletzt abgeschlossenen Wartungen dieses
                            Assets.
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
