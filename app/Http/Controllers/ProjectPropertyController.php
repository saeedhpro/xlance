<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Http\Resources\PropertyCollectionResource;
use App\Http\Resources\PropertyResource;
use App\Interfaces\PropertyInterface;
use App\Models\Image;
use App\Models\ProjectProperty;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ProjectPropertyController extends Controller
{
    protected $propertyRepository;
    public function __construct(PropertyInterface $propertyRepository)
    {
        $this->propertyRepository = $propertyRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return PropertyCollectionResource
     */
    public function index()
    {
        return  new PropertyCollectionResource($this->propertyRepository->all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePropertyRequest $request
     * @return PropertyResource|JsonResponse
     */
    public function store(StorePropertyRequest $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->hasRole('admin')) {
            /** @var ProjectProperty $property */
            $property = $this->propertyRepository->create($request->only([
                'name',
                'description',
                'color',
                'bg_color',
                'price'
            ]));
            if($request->has('icon_id')) {
                $uid = $request->get('icon_id');
                /** @var Upload $upload */
                $upload = Upload::find($uid);
                $img = $property->icon()->create([
                    'name' => $upload->name,
                    'path' => $upload->path,
                ]);
                $property->update([
                    'icon_id' => $img->id,
                ]);
            }
            return  new PropertyResource($property);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return PropertyResource|JsonResponse
     */
    public function show($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->hasRole('admin')) {
            $property = $this->propertyRepository->findOneOrFail($id);
            return  new PropertyResource($property);
        } else {
            return $this->accessDeniedResponse();
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePropertyRequest $request
     * @param $id
     * @return PropertyResource|JsonResponse
     */
    public function update(UpdatePropertyRequest $request, $id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->hasRole(['admin', 'freelancer'])) {
            $this->propertyRepository->update($request->only([
                'name',
                'description',
                'color',
                'bg_color',
                'price'
            ]), $id);
            /** @var ProjectProperty $property */
            $property = $this->propertyRepository->findOneOrFail($id);
            if($request->has('icon_id')) {
                $uid = $request->get('icon_id');
                /** @var Upload $upload */
                $upload = Upload::find($uid);
                if($upload) {
                    if($property->icon()->first()) {
                        $property->icon()->first()->update([
                            'name' => $upload->name,
                            'path' => $upload->path,
                        ]);
                    } else {
                        $image = Image::create([
                            'name' => $upload->name,
                            'path' => $upload->path,
                            'imageable_type' => get_class($property),
                            'imageable_id' => $property->id,
                        ]);
                        $property->update([
                            'icon_id' => $image->id,
                        ]);
                    }
                }
            }
            $property = $this->propertyRepository->find($id);
            return new PropertyResource($property);
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
        /** @var ProjectProperty $property */
        $property = $this->propertyRepository->findOneOrFail($id);
        if($user->hasRole('admin')) {
            try {
                $this->propertyRepository->delete($id);
                return \response()->json(['success' => 'ویژگی با موفقیت حذف شد', 'id' => $id], 200);
            } catch (\Exception $e) {
                return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }
}
