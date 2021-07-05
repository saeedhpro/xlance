<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\ArticleCollectionResource;
use App\Http\Resources\CategoryCollectionResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\SkillCollectionResource;
use App\Interfaces\CategoryInterface;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    protected $categoryRepository;
    public function __construct(CategoryInterface $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return CategoryCollectionResource
     */
    public function articleCategories()
    {
        $categories = $this->categoryRepository->allByType(Category::ARTICLE_TYPE);
        return new CategoryCollectionResource($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreCategoryRequest $request
     * @return CategoryResource
     */
    public function storeArticleCategory(StoreCategoryRequest $request)
    {
        /** @var User $user */
        $user = $request->user();
        if($user->hasRole(['admin', 'writer'])) {
            $request['type'] = 'article';
            $category = $this->categoryRepository->create($request->only([
                'name',
                'type',
                'parent_id',
                'image_id',
            ]));
            return new CategoryResource($category);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return CategoryResource
     */
    public function show($id)
    {
        $category = $this->categoryRepository->findOneOrFail($id);
        return new CategoryResource($category);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateCategoryRequest $request
     * @param $id
     * @return CategoryResource
     */
    public function updateArticleCategory(UpdateCategoryRequest $request, $id)
    {
        /** @var User $user */
        $user = $request->user();
        if($user->hasRole(['admin', 'writer'])) {
            return $this->update($request, $id);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateCategoryRequest $request
     * @param $id
     * @return CategoryResource
     */
    public function update(UpdateCategoryRequest $request, $id)
    {
        /** @var User $user */
        $user = $request->user();
        if($user->hasRole(['admin'])) {
            $category = $this->categoryRepository->update($request->only([
                'name',
                'parent_id',
                'new_image_id',
            ]), $id);
            return new CategoryResource($category);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroyArticleCategory($id)
    {
        /** @var User $user */
        $user = auth()->user();
        if($user->hasRole(['admin', 'writer'])) {
            return $this->destroy($id);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param $id
     * @return ArticleCollectionResource
     */
    public function articles($id)
    {
        /** @var Category $category */
        $category = $this->categoryRepository->findOneOrFail($id);
        $articles = $category->articles()->get();
        return new ArticleCollectionResource($articles);
    }

    /**
     * Display a listing of the resource.
     *
     * @return CategoryCollectionResource
     */
    public function skillCategories()
    {
        $categories = $this->categoryRepository->allByType(Category::SKILL_TYPE);
        return new CategoryCollectionResource($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreCategoryRequest $request
     * @return CategoryResource
     */
    public function storeSkillCategory(StoreCategoryRequest $request)
    {
        /** @var User $user */
        $user = $request->user();
        if($user->hasRole(['admin'])) {
            $request['type'] = Category::SKILL_TYPE;
            $category = $this->categoryRepository->create($request->only([
                'name',
                'type',
                'parent_id',
                'image_id',
            ]));
            return new CategoryResource($category);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateCategoryRequest $request
     * @param $id
     * @return CategoryResource
     */
    public function updateSkillCategory(UpdateCategoryRequest $request, $id)
    {
        /** @var User $user */
        $user = $request->user();
        if($user->hasRole(['admin'])) {
            return $this->update($request, $id);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroySkillCategory($id)
    {
        /** @var User $user */
        $user = auth()->user();
        if($user->hasRole(['admin'])) {
           return $this->destroy($id);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param $id
     * @return SkillCollectionResource
     */
    public function skills($id)
    {
        /** @var Category $category */
        $category = $this->categoryRepository->findOneOrFail($id);
        $skills = $category->skills()->get();
        return new SkillCollectionResource($skills);
    }

    private function destroy($id)
    {
        /** @var Category $category */
        $category = $this->categoryRepository->find($id);
        if ($category) {
            try {
                $this->categoryRepository->delete($id);
                return \response()->json(['success' => 'دسته بندی با موفقیت حذف شد', 'id' => $id], 200);
            } catch (\Exception $e) {
                return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
            }
        } else {
            return \response()->json(['error' => 'دسته بندی یافت نشد'], 404);
        }
    }
}
