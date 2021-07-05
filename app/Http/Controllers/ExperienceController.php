<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExperienceRequest;
use App\Http\Requests\UpdateExperienceRequest;
use App\Http\Resources\ExperienceResource;
use App\Interfaces\ExperienceInterface;
use App\Models\experience;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ExperienceController extends Controller
{
    protected $experienceRepository;
    public function __construct(ExperienceInterface $experienceRepository)
    {
        $this->experienceRepository = $experienceRepository;
    }

    public function show($id)
    {
        /** @var Experience $experience */
        $experience = $this->experienceRepository->findOneOrFail($id);
        return new ExperienceResource($experience);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreExperienceRequest $request
     * @return ExperienceResource
     */
    public function store(StoreExperienceRequest $request)
    {
        $auth = auth()->user();
        $to_date = $request->to_date;
        if($request->get('up_to_now')) {
            $to_date = null;
        }
        $attributes = array(
            'position' => $request->position,
            'company' => $request->company,
            'description' => $request->description,
            'up_to_now' => $request->up_to_now,
            'from_date' => $request->from_date,
            'to_date' => $to_date,
            'user_id' => $auth->id,
        );
        $experience = $this->experienceRepository->create($attributes);
        return new ExperienceResource($experience);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateExperienceRequest $request
     * @param $id
     * @return ExperienceResource
     */
    public function update(UpdateExperienceRequest $request, $id)
    {
        /** @var User $user */
        $user = auth()->user();
        /** @var Experience $experience */
        $experience = $this->experienceRepository->findOneOrFail($id);
        if($user->can('update-experience', $experience)) {
            $to_date = $request->to_date;
            if($request->get('up_to_now')) {
                $to_date = null;
            }
            $attributes = array(
                'position' => $request->position,
                'company' => $request->company,
                'description' => $request->description,
                'up_to_now' => $request->up_to_now,
                'from_date' => $request->from_date,
                'to_date' => $to_date,
            );
            $this->experienceRepository->update($attributes, $experience->id);
            $experience = $this->experienceRepository->findOneOrFail($id);
            return new ExperienceResource($experience);
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
        /** @var Experience $experience */
        $experience = $this->experienceRepository->findOneOrFail($id);
        if($user->can('delete-experience', $experience)) {
            try {
                $this->experienceRepository->delete($id);
                return \response()->json(['success' => 'سابقه کاری با موفقیت حذف شد', 'id' => $id], 200);
            } catch (\Exception $e) {
                return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }
}
