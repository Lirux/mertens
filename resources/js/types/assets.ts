export type AssetStatus = 'active' | 'maintenance' | 'inactive' | 'retired';

export type AssetLocation = {
    site: string;
    building: string;
    room: string;
};

export type AssetSupplier = {
    externalId: string;
    name: string;
};

export type AssetMaintenance = {
    lastCompletedAt: string | null;
    nextDueAt: string;
    intervalDays: number;
    note: string | null;
};

export type AssetMaintenanceActor = {
    id: string | null;
    name: string;
};

export type AssetMaintenanceHistoryEntry = {
    id: string;
    completedAt: string | null;
    nextDueAt: string | null;
    intervalDays: number;
    statusAfter: AssetStatus;
    note: string | null;
    recordedBy: AssetMaintenanceActor;
    recordedAt: string | null;
};

export type AssetSummary = {
    id: string;
    inventoryNumber: string;
    serialNumber: string | null;
    name: string;
    category: string;
    status: AssetStatus;
    location: AssetLocation;
    supplier: AssetSupplier | null;
    acquisitionDate: string | null;
    acquisitionValue: string;
    currency: string;
    warrantyUntil: string | null;
    maintenance: AssetMaintenance | null;
    createdAt: string | null;
    updatedAt: string | null;
};

export type AssetDetail = AssetSummary;

export type AssetFilters = {
    search: string | null;
    status: AssetStatus | null;
    category: string | null;
    maintenanceDue: 'overdue' | 'next_30_days' | null;
};

export type PaginatedAssets = {
    data: AssetSummary[];
    current_page: number;
    first_page_url: string;
    from: number | null;
    last_page: number;
    last_page_url: string;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

export type DashboardStats = {
    total: number;
    createdThisMonth: number;
    maintenanceDue: number;
    overdue: number;
    inactive: number;
};

export type AssetFormValues = {
    inventoryNumber: string;
    serialNumber: string;
    name: string;
    category: string;
    status: AssetStatus;
    location: AssetLocation;
    supplier: AssetSupplier;
    acquisitionDate: string;
    acquisitionValue: string;
    currency: string;
    warrantyUntil: string;
    maintenance: {
        nextDueAt: string;
        intervalDays: string;
    };
};
