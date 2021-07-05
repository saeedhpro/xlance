<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\ArticleCollectionResource;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CommentCollectionResource;
use App\Http\Resources\CommentResource;
use App\Interfaces\ArticleInterface;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ArticleController extends Controller
{
    protected $articleRepository;

    public function __construct(ArticleInterface $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return ArticleCollectionResource
     */
    public function index()
    {
        if (\request()->get('page')) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            $articles = $this->articleRepository->allByPagination('*','created_at', 'DESC', $page, $limit);
        } else {
            $articles = $this->articleRepository->all();
        }
        return new ArticleCollectionResource($articles);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreArticleRequest $request
     * @return ArticleResource
     */
    public function store(StoreArticleRequest $request)
    {
        /** @var User $user */
        $user = $request->user();
        if ($user->hasRole(['admin', 'writer'])) {
            $article = $this->articleRepository->create($request->only([
                'title',
                'body',
                'category_id',
                'image_id',
            ]));
            return new ArticleResource($article);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return ArticleResource
     */
    public function show($id)
    {
        $article = $this->articleRepository->findOneOrFail($id);
        return new ArticleResource($article);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateArticleRequest $request
     * @param $id
     * @return ArticleResource
     */
    public function update(UpdateArticleRequest $request, $id)
    {
        /** @var User $user */
        $user = auth()->user();
        if ($user->hasRole(['admin', 'writer'])) {
            $article = $this->articleRepository->update($request->only([
                'title',
                'body',
                'category_id',
                'new_image_id',
            ]), $id);
            return new ArticleResource($article);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        /** @var User $user */
        $user = auth()->user();
        if ($user->hasRole(['admin', 'writer'])) {
            /** @var Article $article */
            $article = $this->articleRepository->find($id);
            if ($article) {
                try {
                    $this->articleRepository->delete($id);
                    return \response()->json(['success' => 'مقاله با موفقیت حذف شد', 'id' => $id], 200);
                } catch (\Exception $e) {
                    return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
                }
            } else {
                return \response()->json(['error' => 'مقاله یافت نشد'], 404);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }

    public function search($term)
    {
        $articles = $this->articleRepository->search($term);
        return new ArticleCollectionResource($articles);
    }


    /**
     * Display a listing of the resource.
     *
     * @param $id
     * @return CommentCollectionResource
     */
    public function indexComments($id)
    {
        /** @var Article $article */
        $article = $this->articleRepository->findOneOrFail($id);
        if (\request()->get('page')) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            $comments = $article->comments()->paginate($page);
        } else {
            $comments = $article->comments()->get();
        }
        return new CommentCollectionResource($comments);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreCommentRequest $request
     * @param $id
     * @return CommentResource
     */
    public function storeComment(StoreCommentRequest $request, $id)
    {
        /** @var User $user */
        $user = auth()->user();
        /** @var Article $article */
        $article = $this->articleRepository->findOneOrFail($id);
        $request['user_id'] = $user->id;
        $comment = $article->comments()->create($request->only([
            'body',
            'parent_id',
            'user_id'
        ]));
        return new CommentResource($comment);
    }

    /**
     * Display the specified resource.
     *
     * @param Article $article
     * @param Comment $comment
     * @return CommentResource
     */
    public function showComment(Article $article, Comment $comment)
    {
        return new CommentResource($comment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateCommentRequest $request
     * @param Comment $comment
     * @return CommentResource
     */
    public function updateComment(UpdateCommentRequest $request, Comment $comment)
    {
        if (Gate::allows('update-comment', $comment)) {
            $comment->update($request->only([
                'body',
            ]));
            return new CommentResource($comment);
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Article $article
     * @param Comment $comment
     * @return JsonResponse
     */
    public function destroyComment(Article $article, Comment $comment)
    {
        if (Gate::allows('destroy-comment', $article)) {
            if ($comment) {
                try {
                    $id = $comment->id;
                    $comment->delete();
                    return \response()->json(['success' => 'کامنت با موفقیت حذف شد', 'id' => $id], 200);
                } catch (\Exception $e) {
                    return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
                }
            } else {
                return \response()->json(['error' => 'کامنت یافت نشد'], 404);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }

}
