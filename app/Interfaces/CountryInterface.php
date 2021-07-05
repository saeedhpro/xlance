<?php


namespace App\Interfaces;

use Illuminate\Http\Request;

/**
 * Interface CountryInterface
 * @package App\Interfaces;
 */
interface CountryInterface extends BaseInterface
{
    public function cities($id);
}
