<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSecurePaymentRequest;
use App\Http\Resources\SecurePaymentCollectionResource;
use App\Models\SecurePayment;
use App\Models\User;
use Illuminate\Http\Request;

class SecurePaymentController extends Controller
{
    public function searchToOthers(Request $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $term = $request->get('term');
        $limit = $this->getLimit();
        $page = $this->getPage();
        $payments = SecurePayment::filter([
            'to_id' => $auth->id,
            'term' => $term,
        ]);
        return new SecurePaymentCollectionResource($payments->paginate($limit));
    }

    public function searchToMe(Request $request)
    {
        /** @var User $auth */
        $auth = auth()->user();
        $term = $request->get('term');
        $limit = $this->getLimit();
        $page = $this->getPage();
        $payments = SecurePayment::filter([
            'user_id' => $auth->id,
            'term' => $term,
        ]);
        return new SecurePaymentCollectionResource($payments->paginate($limit));
    }
}
