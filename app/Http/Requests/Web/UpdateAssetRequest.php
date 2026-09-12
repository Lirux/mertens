<?php

namespace App\Http\Requests\Web;

use App\Models\Asset;

class UpdateAssetRequest extends AssetWriteRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $asset = $this->route('asset');

        return $asset instanceof Asset && ($this->user()?->can('update', $asset) ?? false);
    }

    protected function existingAsset(): ?Asset
    {
        $asset = $this->route('asset');

        return $asset instanceof Asset ? $asset : null;
    }
}
