<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRequestForProjectRequest;
use App\Http\Resources\RequestResource;
use App\Http\Resources\SecurePaymentCollectionResource;
use App\Interfaces\ProjectRequestInterface;

class RequestController extends Controller
{
    protected $requestRepository;
    public function __construct(ProjectRequestInterface $requestRepository)
    {
        $this->requestRepository = $requestRepository;
    }

    public function show($id)
    {
        $request = $this->requestRepository->findOneOrFail($id);
        return new RequestResource($request);
    }

    public function payments($id)
    {
        $payments = $this->requestRepository->securePayments($id);
        return new SecurePaymentCollectionResource($payments->sortByDesc('created_at'));
    }

    public function update(UpdateRequestForProjectRequest $request, $id)
    {

    }
}
