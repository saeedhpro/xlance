<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\CommentCollectionResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\PostCollectionResource;
use App\Http\Resources\PostResource;
use App\Interfaces\PostInterface;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    protected $postRepository;
    public function __construct(PostInterface $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return PostCollectionResource
     */
    public function index()
    {
        if(\request()->get('page')) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            $posts = $this->postRepository->allByPagination('*','created_at', 'DESC', $page, $limit);
        } else {
            $posts = $this->postRepository->all();
        }
        return new PostCollectionResource($posts);
    }

    /**
     * Display a listing of the resource.
     *
     * @param $id
     * @return PostCollectionResource
     */
    public function userAllPosts($id)
    {
        if(\request()->get('page')) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            $posts = $this->postRepository->userAllPostsByPagination($id, 'created_at', 'desc', $page, $limit);
        } else {
            $posts = $this->postRepository->userAllPosts($id);
        }
        return new PostCollectionResource($posts);
    }

    /**
     * Display a listing of the resource.
     *
     * @return PostCollectionResource
     */
    public function ownAllPosts()
    {
        /** @var User $user */
        $user = auth()->user();
        if(\request()->get('page')) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            $posts = $this->postRepository->userAllPostsByPagination($user->id, 'created_at', 'desc', $page, $limit);
        } else {
            $posts = $this->postRepository->userAllPosts($user->id);
        }
        return new PostCollectionResource($posts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePostRequest $request
     * @return PostResource
     */
    public function store(StorePostRequest $request)
    {
        $post = $this->postRepository->create($request->only([
            'caption',
            'image_id'
        ]));
        $user = auth()->user();
        $text = "$user->first_name $user->last_name پست جدید ایجاد کرده است.";
        $type = Notification::ADMIN_POST;
        Notification::make(
            $type,
            $text,
            null,
            $text,
            get_class($post),
            $post->id,
            true
        );
        return new PostResource($post);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return PostResource
     */
    public function show($id)
    {
        $post = $this->postRepository->findOneOrFail($id);
        return new PostResource($post);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdatePostRequest $request
     * @param $id
     * @return PostResource
     */
    public function update(UpdatePostRequest $request, $id)
    {
        $post = $this->postRepository->findOneOrFail($id);
        if(Gate::allows('update-post', $post)) {
            $post = $this->postRepository->update($request->only([
                'caption',
                'new_image_id'
            ]), $id);
            return new PostResource($post);
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
        $post = $this->postRepository->findOneOrFail($id);
        if(Gate::allows('destroy-post', $post)) {
            if ($post) {
                try {
                    $this->postRepository->delete($id);
                    return \response()->json(['success' => 'پست با موفقیت حذف شد', 'id' => $id], 200);
                } catch (\Exception $e) {
                    return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
                }
            } else {
                return \response()->json(['error' => 'پست یافت نشد'], 404);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param $id
     * @return CommentCollectionResource
     */
    public function indexComments($id)
    {
        /** @var Post $post */
        $post = $this->postRepository->findOneOrFail($id);
        if(\request()->get('page')) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            $comments = $post->comments()->paginate($page);
        } else {
            $comments = $post->comments()->get();
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
        /** @var Post $post */
        $post = $this->postRepository->findOneOrFail($id);
        $request['user_id'] = $user->id;
        $comment = $post->comments()->create($request->only([
            'body',
            'parent_id',
            'user_id'
        ]));
        $text = $user->username . ' برای پست شما کامنت قرار داد';
        $type = Notification::POST;
        Notification::make(
            $type,
            $text,
            $post->user->id,
            $text,
            get_class($post),
            $post->id,
            false
        );
        return new CommentResource($comment);
    }

    /**
     * Display the specified resource.
     *
     * @param Post $post
     * @param Comment $comment
     * @return CommentResource
     */
    public function showComment(Post $post, Comment $comment)
    {
        return new CommentResource($comment);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateCommentRequest $request
     * @param Comment $comment
     * @return CommentResource|JsonResponse
     */
    public function updateComment(UpdateCommentRequest $request, Comment $comment)
    {
        if(Gate::allows('update-comment', $comment)) {
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
     * @param Post $post
     * @param Comment $comment
     * @return JsonResponse
     */
    public function destroyComment(Post $post, Comment $comment)
    {
        if(Gate::allows('destroy-comment', $post)) {
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

    public function like($id)
    {
        return $this->postRepository->like($id);
    }

    public function unLike($id)
    {
        return $this->postRepository->unLike($id);
    }

    public function save($id)
    {
        return $this->postRepository->save($id);
    }

    public function unSave($id)
    {
        return $this->postRepository->unSave($id);
    }

    public function bookmark($id)
    {
        return $this->postRepository->bookmark($id);
    }

    public function unmark($id)
    {
        return $this->postRepository->unmark($id);
    }
}
