<?php


namespace App\Interfaces;

/**
 * Interface StoryInterface
 * @package App\Interfaces;
 */
interface StoryInterface extends BaseInterface
{
    /**
     * Return all model rows
     * @param $user_id
     * @param array $columns
     * @param string $orderBy
     * @param string $sortBy
     * @return mixed
     */
    public function userAllStories($user_id, $columns = array('*'), $orderBy = 'id', $sortBy = 'desc');

    /**
     * Return all model rows by paginate
     * @param $user_id
     * @param string $orderBy
     * @param string $sortBy
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function userAllStoriesByPagination($user_id, $orderBy = 'id', $sortBy = 'desc', $page = 1, $limit = 10);
}
