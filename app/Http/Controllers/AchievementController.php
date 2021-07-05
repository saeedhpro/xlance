<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAchievementRequest;
use App\Http\Requests\UpdateAchievementRequest;
use App\Http\Resources\AchievementResource;
use App\Interfaces\AchievementInterface;
use App\Models\Achievement;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AchievementController extends Controller
{
    protected $achievementRepository;
    public function __construct(AchievementInterface $achievementRepository)
    {
        $this->achievementRepository = $achievementRepository;
    }

    public function show($id)
    {
        /** @var Achievement $achievement */
        $achievement = $this->achievementRepository->findOneOrFail($id);
        return new AchievementResource($achievement);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreAchievementRequest $request
     * @return AchievementResource
     */
    public function store(StoreAchievementRequest $request)
    {
        $auth = auth()->user();
        $attributes = array(
            'title' => $request->title,
            'event_name' => $request->event_name,
            'user_id' => $auth->id,
        );
        $achievement = $this->achievementRepository->create($attributes);
        return new AchievementResource($achievement);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateAchievementRequest $request
     * @param $id
     * @return AchievementResource
     */
    public function update(UpdateAchievementRequest $request, $id)
    {
        /** @var User $user */
        $user = auth()->user();
        /** @var Achievement $achievement */
        $achievement = $this->achievementRepository->findOneOrFail($id);
        if($user->can('update-achievement', $achievement)) {
            $attributes = array(
                'title' => $request->title,
                'event_name' => $request->event_name,
            );
            $this->achievementRepository->update($attributes, $achievement->id);
            $achievement = $this->achievementRepository->findOneOrFail($id);
            return new AchievementResource($achievement);
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
        /** @var Achievement $achievement */
        $achievement = $this->achievementRepository->findOneOrFail($id);
        if($user->can('delete-achievement', $achievement)) {
            try {
                $this->achievementRepository->delete($id);
                return \response()->json(['success' => 'سابقه کاری با موفقیت حذف شد', 'id' => $id], 200);
            } catch (\Exception $e) {
                return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }
}
