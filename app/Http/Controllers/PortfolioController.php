<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddPortfolioImageRequest;
use App\Http\Requests\StorePortfolioRequest;
use App\Http\Requests\UpdatePortfolioRequest;
use App\Http\Resources\AssetResource;
use App\Http\Resources\PortfolioResource;
use App\Interfaces\PortfolioInterface;
use App\Models\Portfolio;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    protected $portfolioRepository;
    public function __construct(PortfolioInterface $portfolioRepository)
    {
        $this->portfolioRepository = $portfolioRepository;
    }

    public function show($id)
    {
        $portfolio = $this->portfolioRepository->findOneOrFail($id);
        return new PortfolioResource($portfolio);
    }

    public function like($id)
    {
        $portfolio = $this->portfolioRepository->like($id);
        return new PortfolioResource($portfolio);
    }

    public function unlike($id)
    {
        $portfolio = $this->portfolioRepository->unlike($id);
        return new PortfolioResource($portfolio);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StorePortfolioRequest $request
     * @return portfolioResource
     */
    public function store(StorePortfolioRequest $request)
    {
        $auth = auth()->user();
        $tags = $this->getTags($request);
        $attributes = array(
            'title' => $request->title,
            'status' => Portfolio::CREATED_STATUS,
            'description' => $request->description,
            'tags' => $tags,
            'user_id' => $auth->id,
        );
        /** @var Portfolio $portfolio */
        $portfolio = $this->portfolioRepository->create($attributes);
        $portfolio->skills()->sync($request->get('skills'));
        $portfolio = $this->syncAttachments($request, $portfolio);
        if($request->has('new_images')) {
            $portfolio = $this->syncImages($request, $portfolio);
        }
        return new PortfolioResource($portfolio);
    }

    private function syncAttachments(Request $request, Portfolio $portfolio)
    {
        $new_ids = $request->get('new_attachments');
        if($new_ids != null && count($new_ids) > 0) {
            foreach ($new_ids as $new_id) {
                $upload = Upload::find($new_id);
                if($upload) {
                    $portfolio->attachments()->create([
                        'name' => $upload->name,
                        'path' => $upload->path,
                        'user_id' => auth()->user()->id,
                    ]);
                }
            }
        }
        return $portfolio;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateportfolioRequest $request
     * @param $id
     * @return portfolioResource
     */
    public function update(UpdateportfolioRequest $request, $id)
    {
        /** @var User $user */
        $user = auth()->user();
        /** @var Portfolio $portfolio */
        $portfolio = $this->portfolioRepository->findOneOrFail($id);
        if($user->can('update-portfolio', $portfolio)) {
            $tags = $this->getTags($request);
            $attributes = array(
                'title' => $request->title,
                'description' => $request->description,
                'tags' => $tags,
            );
            $this->portfolioRepository->update($attributes, $portfolio->id);
            $portfolio = $this->portfolioRepository->findOneOrFail($id);
            $portfolio->skills()->sync($request->get('skills'));
            return new PortfolioResource($portfolio);
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
        /** @var Portfolio $portfolio */
        $portfolio = $this->portfolioRepository->findOneOrFail($id);
        if($user->can('destroy-portfolio', $portfolio)) {
            try {
                $this->portfolioRepository->delete($id);
                return \response()->json(['success' => 'نمونه کار با موفقیت حذف شد', 'id' => $id], 200);
            } catch (\Exception $e) {
                return \response()->json(['error' => 'متاسفانه خطایی رخ داده است'], 500);
            }
        } else {
            return $this->accessDeniedResponse();
        }
    }

    private function getTags(FormRequest $request) : string
    {
        if($request->has('tags')){
            $tag_list = $request->get('tags');
            if(!empty($tag_list)) {
                return implode(',', $tag_list);
            }
        }
        return '';
    }

    private function syncImages(Request $request, Portfolio $portfolio)
    {
        $new_ids = $request->get('new_images');
        if(count($new_ids) > 0) {
            foreach ($new_ids as $new_id) {
                $upload = Upload::find($new_id);
                if($upload) {
                    $portfolio->images()->create([
                        'name' => $upload->name,
                        'path' => $upload->path,
                        'user_id' => auth()->user()->id,
                    ]);
                }
            }
        }
        return $portfolio;
    }

    public function addImage(AddPortfolioImageRequest $request, $id) {
        $image = $this->portfolioRepository->addImage($request, $id);
        return new AssetResource($image);
    }

    public function destroyImage($id, $image_id){
        return $this->portfolioRepository->destroyImage($id, $image_id);
    }
}
