<?php


namespace App\Interfaces;

use App\Http\Requests\StoreSecurePaymentRequest;
use Illuminate\Http\Request;

/**
 * Interface SecurePaymentInterface
 * @package App\Interfaces;
 */
interface SecurePaymentInterface extends BaseInterface
{
    public function store(StoreSecurePaymentRequest $request);
}
