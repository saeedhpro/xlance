<?php


namespace App\Interfaces;

use Illuminate\Http\Request;

/**
 * Interface CityInterface
 * @package App\Interfaces;
 */
interface CityInterface extends BaseInterface
{
    public function users($id);
}
