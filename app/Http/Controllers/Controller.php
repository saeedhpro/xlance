<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function accessDeniedResponse () {
        return response()->json(['error' => 'شما به این بخش دسترسی ندارید'], 403);
    }

    protected function customResponse () {
        return response()->json(['status' => 'شما به این بخش دسترسی ندارید'], 403);
    }

    protected function getAuth() {
        return auth()->user();
    }

    public function hasPage()
    {
        return \request()->has('page');
    }

    protected function getPage() {
        $page = \request()->get('page');
        $page = is_null($page) ? 10 : $page;
        return $page;
    }

    protected function getLimit() {
        $limit = \request()->get('limit');
        $limit = is_null($limit) ? 10 : $limit;
        return $limit;
    }

    function paginateCollection($collection, $perPage, $pageName = 'page', $fragment = null)
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage($pageName);
        $currentPageItems = $collection->slice(($currentPage - 1) * $perPage, $perPage);
        parse_str(request()->getQueryString(), $query);
        unset($query[$pageName]);
        $paginator = new LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            $perPage,
            $currentPage,
            [
                'pageName' => $pageName,
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $query,
                'fragment' => $fragment
            ]
        );

        return $paginator;
    }

    protected function isLike($first, $second): bool {
        return str_contains($first, $second);
    }

    protected function getNonce() {
        try {
            $nonce = random_int(0, 2 ^ 1024);
        } catch (\Exception $e) {
            $nonce = 1024;
        }
        return $nonce;
    }
}
