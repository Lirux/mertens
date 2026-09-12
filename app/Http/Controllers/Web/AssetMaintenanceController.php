<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\UpdateAssetMaintenanceRequest;
use App\Http\Resources\Web\AssetMaintenanceHistoryResource;
use App\Http\Resources\Web\AssetResource;
use App\Models\Asset;
use App\Models\User;
use App\Repositories\AssetRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AssetMaintenanceController extends Controller
{
    public function __construct(private readonly AssetRepository $assets) {}

    public function edit(Asset $asset): Response
    {
        Gate::authorize('update', $asset);

        return Inertia::render('assets/maintenance', [
            'asset' => (new AssetResource($asset))->resolve(),
            'maintenanceHistory' => AssetMaintenanceHistoryResource::collection(
                $this->assets->maintenanceHistory($asset, 3),
            )->resolve(),
        ]);
    }

    public function update(
        UpdateAssetMaintenanceRequest $request,
        Asset $asset,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $asset = $this->assets->recordMaintenance(
            $asset,
            $request->assetAttributes(),
            (string) $user->getAuthIdentifier(),
            $user->name,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Wartung wurde erfasst und der Folgetermin aktualisiert.',
        ]);

        return to_route('assets.show', $asset);
    }
}
