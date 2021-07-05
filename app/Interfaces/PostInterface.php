<?php


namespace App\Interfaces;

use Illuminate\Http\Request;

/**
 * Interface PostInterface
 * @package App\Interfaces;
 */
interface PostInterface extends BaseInterface
{
    /**
     * Return all model rows
     * @param $user_id
     * @param array $columns
     * @param string $orderBy
     * @param string $sortBy
     * @return mixed
     */
    public function userAllPosts($user_id, $columns = array('*'), $orderBy = 'id', $sortBy = 'desc');

    /**
     * Return all model rows by paginate
     * @param $user_id
     * @param string $orderBy
     * @param string $sortBy
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function userAllPostsByPagination($user_id, $orderBy = 'id', $sortBy = 'desc', $page = 1, $limit = 10);

    public function like($id);
    public function unLike($id);

    public function save($id);
    public function unSave($id);

    public function bookmark($id);
    public function unmark($id);

}
