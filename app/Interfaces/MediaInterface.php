<?php


namespace App\Interfaces;

use Illuminate\Http\Request;

/**
 * Interface MediaInterface
 * @package App\Interfaces;
 */
interface MediaInterface extends BaseInterface
{
    /**
     * Return all model rows
     * @param string $type
     * @return mixed
     */
    public function allByType($type);


    /**
     * Return all model rows
     * @param string $type
     * @param int $page
     * @return mixed
     */
    public function allByTypeByPagination($type, $page = 1);
}
