<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListAssetsRequest;
use App\Http\Requests\Api\V1\StoreAssetRequest;
use App\Http\Requests\Api\V1\UpdateAssetRequest;
use App\Http\Resources\Api\V1\AssetResource;
use App\Models\Asset;
use App\Repositories\AssetRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AssetController extends Controller
{
    public function __construct(private readonly AssetRepository $assets) {}

    public function index(ListAssetsRequest $request): AnonymousResourceCollection
    {
        return AssetResource::collection($this->assets->list(
            updatedSince: $request->updatedSince(),
            limit: $request->limit(),
            offset: $request->offset(),
        ));
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $asset = $this->assets->create($request->assetAttributes());

        return (new AssetResource($asset))->response()->setStatusCode(201);
    }

    public function show(Asset $asset): AssetResource
    {
        return new AssetResource($asset);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): AssetResource
    {
        return new AssetResource($this->assets->update($asset, $request->assetAttributes()));
    }

    public function destroy(Asset $asset): Response
    {
        $this->assets->delete($asset);

        return response()->noContent();
    }
}
