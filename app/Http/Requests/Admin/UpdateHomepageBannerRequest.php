<?php

namespace App\Http\Requests\Admin;

class UpdateHomepageBannerRequest extends StoreHomepageBannerRequest
{
    public function rules(): array
    {
        return parent::rules() + ['image' => ['nullable', 'file', 'extensions:jpg,jpeg,png,webp,gif,avif', 'max:10240']];
    }
}
