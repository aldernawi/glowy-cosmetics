<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTaxonomyRequest;
use App\Models\Concern;
use App\Models\Ingredient;
use App\Models\SkinType;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class TaxonomyController extends Controller
{
    public function concerns(): View
    {
        return $this->index(Concern::class, 'احتياجات البشرة', 'concerns');
    }

    public function skinTypes(): View
    {
        return $this->index(SkinType::class, 'أنواع البشرة', 'skin-types');
    }

    public function ingredients(): View
    {
        return $this->index(Ingredient::class, 'المكونات', 'ingredients');
    }

    public function storeConcern(StoreTaxonomyRequest $request): RedirectResponse
    {
        return $this->store($request, Concern::class, 'concerns');
    }

    public function storeSkinType(StoreTaxonomyRequest $request): RedirectResponse
    {
        return $this->store($request, SkinType::class, 'skin-types');
    }

    public function storeIngredient(StoreTaxonomyRequest $request): RedirectResponse
    {
        return $this->store($request, Ingredient::class, 'ingredients');
    }

    public function updateConcern(StoreTaxonomyRequest $request, Concern $concern): RedirectResponse
    {
        return $this->update($request, $concern, 'concerns');
    }

    public function updateSkinType(StoreTaxonomyRequest $request, SkinType $skinType): RedirectResponse
    {
        return $this->update($request, $skinType, 'skin-types');
    }

    public function updateIngredient(StoreTaxonomyRequest $request, Ingredient $ingredient): RedirectResponse
    {
        return $this->update($request, $ingredient, 'ingredients');
    }

    public function destroyConcern(Concern $concern): RedirectResponse
    {
        return $this->destroy($concern, 'concerns');
    }

    public function destroySkinType(SkinType $skinType): RedirectResponse
    {
        return $this->destroy($skinType, 'skin-types');
    }

    public function destroyIngredient(Ingredient $ingredient): RedirectResponse
    {
        return $this->destroy($ingredient, 'ingredients');
    }

    /** @param class-string<Model> $model */
    private function index(string $model, string $label, string $route): View
    {
        return view('admin.taxonomy.simple', ['items' => $model::query()->orderBy('name_ar')->get(), 'label' => $label, 'route' => $route]);
    }

    /** @param class-string<Model> $model */
    private function store(StoreTaxonomyRequest $request, string $model, string $route): RedirectResponse
    {
        $validated = $request->validated();
        validator($validated, ['slug' => [Rule::unique((new $model)->getTable(), 'slug')]])->validate();
        $model::query()->create($validated + ['is_active' => $request->boolean('is_active')]);

        return to_route("admin.{$route}.index")->with('status', 'تمت الإضافة.');
    }

    private function update(StoreTaxonomyRequest $request, Model $item, string $route): RedirectResponse
    {
        $validated = $request->validated();
        validator($validated, ['slug' => [Rule::unique($item->getTable(), 'slug')->ignore($item)]])->validate();
        $item->update($validated + ['is_active' => $request->boolean('is_active')]);

        return to_route("admin.{$route}.index");
    }

    private function destroy(Model $item, string $route): RedirectResponse
    {
        $item->delete();

        return to_route("admin.{$route}.index");
    }
}
