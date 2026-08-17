<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Asset;

class UpdateAssetRequest extends AssetWriteRequest
{
    protected function existingAsset(): ?Asset
    {
        $asset = $this->route('asset');

        return $asset instanceof Asset ? $asset : null;
    }
}
