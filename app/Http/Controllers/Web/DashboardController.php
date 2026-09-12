<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\Web\AssetResource;
use App\Models\Asset;
use App\Repositories\AssetRepository;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly AssetRepository $assets) {}

    public function __invoke(): Response
    {
        Gate::authorize('viewAny', Asset::class);

        $summary = $this->assets->dashboardSummary(now());
        $upcoming = $summary['upcoming']
            ->map(fn (Asset $asset): array => (new AssetResource($asset))->resolve())
            ->values()
            ->all();

        return Inertia::render('dashboard', [
            'stats' => [
                'total' => $summary['total'],
                'createdThisMonth' => $summary['createdThisMonth'],
                'maintenanceDue' => $summary['maintenanceDue'],
                'overdue' => $summary['overdue'],
                'inactive' => $summary['inactive'],
            ],
            'upcomingMaintenance' => $upcoming,
        ]);
    }
}
