<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequestPackageRequest;
use App\Http\Requests\UpdateRequestPackageRequest;
use App\Http\Resources\RequestPackageCollectionResource;
use App\Http\Resources\RequestPackageResource;
use App\Interfaces\RequestPackageInterface;
use App\Models\User;

class RequestPackageController extends Controller
{
    protected $requestPackageRepository;

    public function __construct(RequestPackageInterface $requestPackageRepository)
    {
        $this->requestPackageRepository = $requestPackageRepository;
    }

    public function index()
    {
        if(request()->has('page')) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            $packages = $this->requestPackageRepository->allByPagination('*', 'desc', 'created_at', $page, $limit)->get();
            return new RequestPackageCollectionResource($packages);
        } else {
            $packages = $this->requestPackageRepository->all();
            return new RequestPackageCollectionResource($packages);
        }
    }

    public function show($id)
    {
        $package = $this->requestPackageRepository->findOneOrFail($id);
        return new RequestPackageResource($package);
    }

    public function store(StoreRequestPackageRequest $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->hasRole('admin')) {
            $package = $this->requestPackageRepository->create($request->only([
            'color',
            'title',
            'type',
            'description',
            'price',
            'number',
        ]));
            return new RequestPackageResource($package);
        } else {
           return $this->accessDeniedResponse();
        }
    }

    public function update(UpdateRequestPackageRequest $request, $id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        if($auth->hasRole('admin')) {
            $this->requestPackageRepository->update($request->only([
                'color',
                'title',
                'type',
                'description',
                'price',
                'number'
            ]), $id);
            $package = $this->requestPackageRepository->findOneOrFail($id);
            return new RequestPackageResource($package);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function ownRequestPackage()
    {
        return new RequestPackageResource($this->requestPackageRepository->ownRequestPackage());
    }

    public function userRequestPackage($id)
    {
        return new RequestPackageResource($this->requestPackageRepository->userRequestPackage($id));
    }
}
