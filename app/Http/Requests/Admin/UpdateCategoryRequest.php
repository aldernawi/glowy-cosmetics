<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.access') ?? false;
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('categories')->ignore($category)],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $category = $this->route('category');
            $parentId = $this->integer('parent_id');
            if (! $category instanceof Category || $parentId === 0) {
                return;
            }
            $descendants = [$category->id];
            $frontier = [$category->id];
            while ($frontier !== []) {
                $frontier = Category::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
                $descendants = [...$descendants, ...$frontier];
            }
            if (in_array($parentId, $descendants, true)) {
                $validator->errors()->add('parent_id', 'لا يمكن جعل التصنيف تابعًا لنفسه أو لأحد فروعه.');
            }
        }];
    }
}
