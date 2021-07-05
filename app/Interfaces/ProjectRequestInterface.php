<?php


namespace App\Interfaces;

use Illuminate\Http\Request;

/**
 * Interface ProjectRequestInterface
 * @package App\Interfaces;
 */
interface ProjectRequestInterface extends BaseInterface
{
    public function securePayments($id);
}
