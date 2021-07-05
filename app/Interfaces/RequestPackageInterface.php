<?php


namespace App\Interfaces;

use Illuminate\Http\Request;

/**
 * Interface RequestPackageInterface
 * @package App\Interfaces;
 */
interface RequestPackageInterface extends BaseInterface
{
    public function ownRequestPackage();
    public function userRequestPackage($id);
}
