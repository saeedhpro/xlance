<?php


namespace App\Repositories;


use App\Http\Resources\NotificationResource;
use App\Http\Resources\StoryCollectionResource;
use App\Interfaces\StoryInterface;
use App\Jobs\SendStoryNotification;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Story;
use App\Models\Upload;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class StoryRepository extends BaseRepository implements StoryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    /**
     * Return all model rows
     * @param array $columns
     * @param string $orderBy
     * @param string $sortBy
     * @return mixed
     */
    public function all($columns = array('*'), $orderBy = 'id', $sortBy = 'desc') {
        /** @var User $user */
        $user = auth()->user();
        $followings = $user->followings;
        $followings = $followings->pluck('id');
        $admins = User::all()->filter(function (User $user) {
            return $user->hasRole('admin');
        })->pluck('id');
        $followings->push($user->id);
        $followings->push($admins->toArray());
        $users = User::with('stories')->has('stories', '>', 0)->where(function ($q) use($followings,$admins) {
            $q->whereIn('users.id', $followings);
        });
        return $users->get();
    }
    /**
     * Return all model rows
     * @param array $columns
     * @param string $orderBy
     * @param string $sortBy
     * @return mixed
     */
    public function ownStories($columns = array('*'), $orderBy = 'id', $sortBy = 'desc') {
        /** @var User $user */
        $user = auth()->user();
        $stories = $this->model->where('user_id', '=', $user->id)->get();
        return new StoryCollectionResource($stories);
    }

    /**
     * Return all model rows by paginate
     * @param string $orderBy
     * @param string $sortBy
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function allOwnStoriesByPagination($orderBy = 'id', $sortBy = 'desc', $page = 1, $limit = 10) {
        /** @var User $user */
        $user = auth()->user();
        return $this->model->orderBy($orderBy, $sortBy)->where('user_id', '=', $user->id)->where('created_at', '>', Carbon::now()->subHours(24))->paginate($page);
    }
    /**
     * Return all model rows by paginate
     * @param string $orderBy
     * @param string $sortBy
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function allOwnStories($orderBy = 'id', $sortBy = 'desc') {
        /** @var User $user */
        $user = auth()->user();
        return $this->model->orderBy($orderBy, $sortBy)->where('user_id', '=', $user->id)->where('created_at', '>', Carbon::now()->subHours(24))->get();
    }

    /**
     * Return all model rows
     * @param $user_id
     * @param array $columns
     * @param string $orderBy
     * @param string $sortBy
     * @return mixed
     */
    public function userAllStories($user_id, $columns = array('*'), $orderBy = 'id', $sortBy = 'desc')
    {
        return $this->model->where('user_id', '=', $user_id)->get();
    }

    /**
     * Return all model rows by paginate
     * @param $user_id
     * @param string $orderBy
     * @param string $sortBy
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function userAllStoriesByPagination($user_id, $orderBy = 'id', $sortBy = 'desc', $page = 1, $limit = 10)
    {
        return $this->model->where('user_id', '=', $user_id)->paginate($page);
    }

    /**
     * Create a model instance
     * @param array $attributes
     * @return mixed
     */
    public function create(array $attributes) {
        /** @var User $user */
        $user = auth()->user();
        /** @var Story $story */
        $story = $this->model->create(array(
            'user_id' => $user->id,
        ));
        if($attributes['image_id']) {
            $story = $this->setImage($story, $attributes['image_id']);
        }
        $text = 'استوری ایجاد شد';
        $type = Notification::STORY;
        Notification::make(
            $type,
            $text,
            $user->id,
            $text,
            get_class($story),
            $story->id,
            false,
        );
        return $story;
    }

    /**
     * Create a model instance
     * @param array $attributes
     * @param $id
     * @return mixed
     */
    public function update(array $attributes, $id)
    {
        $story = $this->find($id);
        if($attributes['new_image_id']) {
            $story = $this->setImage($story, $attributes['new_image_id']);
        }
        return $story;
    }

    private function setImage(Story $story, $image_id)
    {
        $upload = Upload::find($image_id);
        if($upload) {
            $story->media()->delete();
            $story->media()->create([
                'name' => $upload->name,
                'path' => $upload->path,
                'type' => Media::IMAGE_TYPE,
            ]);
        }
        return $story;
    }
}
