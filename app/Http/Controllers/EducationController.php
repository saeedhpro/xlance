<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEducationRequest;
use App\Http\Requests\UpdateEducationRequest;
use App\Http\Resources\EducationResource;
use App\Interfaces\EducationInterface;
use App\Models\Education;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EducationController extends Controller
{
    protected $educationRepository;
    public function __construct(EducationInterface $educationRepository)
    {
        $this->educationRepository = $educationRepository;
    }

    public function show($id)
    {
        /** @var Education $educaion */
        $educaion = $this->educationRepository->findOneOrFail($id);
        return new EducationResource($educaion);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreEducationRequest $request
     * @return EducationResource
     */
    public function store(StoreEducationRequest $request)
    {
        $auth = auth()->user();
        $to_date = $request->to_date;
        if($request->get('up_to_now')) {
            $to_date = null;
        }
        $attributes = array(
            'degree' => $request->degree,
            'school_name' => $request->school_name,
            'up_to_now' => $request->up_to_now,
            'from_date' => $request->from_date,
            'to_date' => $to_date,
            'user_id' => $auth->id,
        );
        $education = $this->educationRepository->create($attributes);
        return new EducationResource($education);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateEducationRequest $request
     * @param $id
     * @return EducationResource
     */
    public function update(UpdateEducationRequest $request, $id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Education $education */
        $education = $this->educationRepository->findOneOrFail($id);
        if($auth->can('update-education', $education)) {
            $to_date = $request->to_date;
            if($request->get('up_to_now')) {
                $to_date = null;
            }
            $attributes = array(
                'degree' => $request->degree,
                'school_name' => $request->school_name,
                'up_to_now' => $request->up_to_now,
                'from_date' => $request->from_date,
                'to_date' => $to_date,
                'user_id' => $auth->id,
            );
            $this->educationRepository->update($attributes, $education->id);
            $education = $this->educationRepository->findOneOrFail($id);
            return new EducationResource($education);
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
        /** @var Education $education */
        $education = $this->educationRepository->findOneOrFail($id);
        if($user->can('delete-education', $education)) {
            try {
                $this->educationRepository->delete($id);
                return \response()->json(['success' => 'تحصیلات با موفقیت حذف شد', 'id' => $id], 200);
            } catch (\Exception $e) {
                return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }
}
