import type { AssetStatus } from '@/types';

export const assetStatusLabels: Record<AssetStatus, string> = {
    active: 'Aktiv',
    maintenance: 'Wartung',
    inactive: 'Ausser Betrieb',
    retired: 'Ausgemustert',
};

const categoryLabels: Record<string, string> = {
    production: 'Produktion',
    logistics: 'Logistik',
    measurement: 'Messtechnik',
};

export function formatAssetCategory(category: string): string {
    return categoryLabels[category] ?? category;
}

export function formatAssetDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('de-CH').format(
        new Date(`${value}T00:00:00`),
    );
}

export function formatAssetDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('de-CH', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export function formatAssetCurrency(value: string, currency: string): string {
    return new Intl.NumberFormat('de-CH', {
        style: 'currency',
        currency,
    }).format(Number(value));
}
