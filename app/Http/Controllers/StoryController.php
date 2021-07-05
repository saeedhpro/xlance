<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStoryRequest;
use App\Http\Requests\UpdateStoryRequest;
use App\Http\Resources\StoryCollectionResource;
use App\Http\Resources\StoryResource;
use App\Http\Resources\StoryUserCollectionResource;
use App\Http\Resources\StoryUserResource;
use App\Interfaces\StoryInterface;
use App\Models\Notification;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class StoryController extends Controller
{
    protected $storyRepository;
    public function __construct(StoryInterface $storyRepository)
    {
        $this->storyRepository = $storyRepository;
    }
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index()
    {
        $users = $this->storyRepository->all();
        return new StoryUserCollectionResource($users);
    }
    /**
     * Display a listing of the resource.
     *
     * @return StoryCollectionResource
     */
    public function ownStories()
    {
        if(\request()->get('page')) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            $stories = $this->storyRepository->allOwnStoriesByPagination('desc', 'created_at', $page, $limit);
        } else {
            $stories = $this->storyRepository->allOwnStories();
        }
        return new StoryCollectionResource($stories);
    }

    public function indexUserStories(User $user)
    {
        if(\request()->get('page')) {
            $page = $this->getPage();
            $limit = $this->getLimit();
            $stories = $this->storyRepository->userAllStoriesByPagination($user->id, 'desc', 'created_at', $page, $limit);
        } else {
            $stories = $this->storyRepository->userAllStories($user->id);
        }
        return new StoryCollectionResource($stories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreStoryRequest $request
     * @return StoryResource
     */
    public function store(StoreStoryRequest $request)
    {
        $story = $this->storyRepository->create($request->only([
            'image_id'
        ]));
        return new StoryResource($story);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return StoryResource
     */
    public function show($id)
    {
        $story = $this->storyRepository->findOneOrFail($id);
        return new StoryResource($story);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateStoryRequest $request
     * @param $id
     * @return StoryResource
     */
    public function update(UpdateStoryRequest $request, $id)
    {
        $story = $this->storyRepository->findOneOrFail($id);
        if(Gate::allows('update-story', $story)) {
            /** @var Story $story */
            $story = $this->storyRepository->update($request->only([
                'new_image_id'
            ]), $id);
            return new StoryResource($story);
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
        $story = $this->storyRepository->findOneOrFail($id);
        if(Gate::allows('destroy-story', $story)) {
            if ($story) {
                try {
                    $this->storyRepository->delete($id);
                    return \response()->json(['success' => 'استوری با موفقیت حذف شد', 'id' => $id], 200);
                } catch (\Exception $e) {
                    return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
                }
            } else {
                return \response()->json(['error' => 'استوری یافت نشد'], 404);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }
}
