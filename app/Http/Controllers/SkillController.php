<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSkillRequest;
use App\Http\Requests\UpdateSkillRequest;
use App\Http\Resources\SkillCollectionResource;
use App\Http\Resources\SkillResource;
use App\Interfaces\SkillInterface;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class SkillController extends Controller
{
    protected $skillRepository;
    public function __construct(SkillInterface $skillRepository)
    {
        $this->skillRepository = $skillRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return SkillCollectionResource
     */
    public function index()
    {
        if(\request()->get('page')) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            $skills = $this->skillRepository->allByPagination('*', 'created_at', 'desc', $page, $limit);
        } else {
            $skills = $this->skillRepository->all();
        }
        return new SkillCollectionResource($skills);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreSkillRequest $request
     * @return SkillResource
     */
    public function store(StoreSkillRequest $request)
    {
        /** @var User $user */
        $user = $request->user();
        if($user->hasRole(['admin'])) {
            $keywords_list = $request->get('keywords_list') ? $request->get('keywords_list') : array();
            $keywords = implode(',', $keywords_list);
            $attributes = array(
                'name' => $request->name,
                'category_id' => $request->category_id,
                'color' => $request->color,
                'status' => true,
                'keywords' => strtolower($keywords),
            );
            $skill = $this->skillRepository->create($attributes);
            return new SkillResource($skill);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return SkillResource
     */
    public function show($id)
    {
        $skill = $this->skillRepository->findOneOrFail($id);
        return new SkillResource($skill);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateSkillRequest $request
     * @param $id
     * @return SkillResource
     */
    public function update(UpdateSkillRequest $request, $id)
    {
        /** @var User $user */
        $user = auth()->user();
        if($user->hasRole(['admin'])) {
            $keywords_list = $request->get('keywords_list');
            $keywords = implode(',', $keywords_list);
            $attributes = array(
                'name' => $request->name,
                'category_id' => $request->category_id,
                'color' => $request->color,
                'status' => $request->status,
                'keywords' => $keywords,
            );
            $this->skillRepository->update($attributes, $id);
            $skill = $this->skillRepository->find($id);
            return new SkillResource($skill);
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
    public function destroy($id)
    {
        /** @var User $user */
        $user = auth()->user();
        if($user->hasRole(['admin'])) {
            try {
                $this->skillRepository->delete($id);
                return \response()->json(['success' => 'مهارت با موفقیت حذف شد', 'id' => $id], 200);
            } catch (\Exception $e) {
                return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }
}
