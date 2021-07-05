<?php


namespace App\Interfaces;

/**
 * Interface ArticleInterface
 * @package App\Interfaces;
 */
interface ArticleInterface extends BaseInterface
{
    public function search($term);
}
