<?php


namespace App\Interfaces;

use Illuminate\Http\Request;

/**
 * Interface CategoryInterface
 * @package App\Interfaces;
 */
interface CategoryInterface extends BaseInterface
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
