<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\DuplicateAssetIdentifierException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\AssetIndexRequest;
use App\Http\Requests\Web\StoreAssetRequest;
use App\Http\Requests\Web\UpdateAssetRequest;
use App\Http\Resources\Web\AssetMaintenanceHistoryResource;
use App\Http\Resources\Web\AssetResource;
use App\Models\Asset;
use App\Repositories\AssetRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function __construct(private readonly AssetRepository $assets) {}

    public function index(AssetIndexRequest $request): Response
    {
        $filters = $request->filters();
        $assets = $this->assets->paginate(
            perPage: 20,
            search: $filters['search'],
            status: $filters['status'],
            category: $filters['category'],
            maintenanceDue: $filters['maintenanceDue'],
        )->withQueryString();

        $assets->through(fn (Asset $asset): array => $this->present($asset));

        return Inertia::render('assets/index', [
            'assets' => $assets,
            'filters' => $filters,
            'categories' => $this->assets->categories(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Asset::class);

        return Inertia::render('assets/create', [
            'categories' => $this->assets->categories(),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        try {
            $asset = $this->assets->create($request->assetAttributes());
        } catch (DuplicateAssetIdentifierException $exception) {
            $this->throwDuplicateValidationException($exception);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Asset wurde erfolgreich erstellt.',
        ]);

        return to_route('assets.show', $asset);
    }

    public function show(Asset $asset): Response
    {
        Gate::authorize('view', $asset);

        return Inertia::render('assets/show', [
            'asset' => $this->present($asset),
            'maintenanceHistory' => AssetMaintenanceHistoryResource::collection(
                $this->assets->maintenanceHistory($asset),
            )->resolve(),
        ]);
    }

    public function edit(Asset $asset): Response
    {
        Gate::authorize('update', $asset);

        return Inertia::render('assets/edit', [
            'asset' => $this->present($asset),
            'categories' => $this->assets->categories(),
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        try {
            $asset = $this->assets->update($asset, $request->assetAttributes());
        } catch (DuplicateAssetIdentifierException $exception) {
            $this->throwDuplicateValidationException($exception);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Änderungen wurden gespeichert.',
        ]);

        return to_route('assets.show', $asset);
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        Gate::authorize('delete', $asset);
        $this->assets->delete($asset);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Asset wurde gelöscht.',
        ]);

        return to_route('assets.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Asset $asset): array
    {
        return (new AssetResource($asset))->resolve();
    }

    private function throwDuplicateValidationException(
        DuplicateAssetIdentifierException $exception,
    ): never {
        $field = $exception->field === 'serial_number' ? 'serialNumber' : 'inventoryNumber';

        throw ValidationException::withMessages([
            $field => 'Diese Nummer ist bereits einem anderen Asset zugeordnet.',
        ]);
    }
}
