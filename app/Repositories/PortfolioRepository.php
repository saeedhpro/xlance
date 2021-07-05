<?php


namespace App\Repositories;

use App\Http\Requests\AddPortfolioImageRequest;
use App\Http\Resources\NotificationResource;
use App\Interfaces\PortfolioInterface;
use App\Models\Notification;
use App\Models\Portfolio;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\Null_;

class PortfolioRepository extends BaseRepository implements PortfolioInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        parent::__construct($model);
    }

    public function addImage(AddPortfolioImageRequest $request, $id)
    {
        /** @var Portfolio $portfolio */
        $portfolio = $this->findOneOrFail($id);
        $upload = Upload::find($request->get('image_id'));
        if($upload) {
            return $portfolio->images()->create([
                'name' => $upload->name,
                'path' => $upload->path,
                'user_id' => auth()->user()->id,
            ]);
        }
        return null;
    }

    public function destroyImage($id, $image_id)
    {/** @var Portfolio $portfolio */
        $portfolio = $this->findOneOrFail($id);
        $image = $portfolio->images()->find($image_id);
        try {
            $image->delete();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function like($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Portfolio $portfolio */
        $portfolio = $this->findOneOrFail($id);
        $auth->like($portfolio);
        $notification = $portfolio->notifications()->create(array(
            'text' => 'نمونه کار لایک شد',
            'type' => Notification::PORTFOLIO,
            'user_id' => $auth->id,
            'image_id' => $auth->profile->avatar ? $auth->profile->avatar->id : null
        ));
        Notification::sendNotificationToAll([$portfolio->user->email], 'نمونه کار لایک شد', 'نمونه کار لایک شد', null);
        return true;
    }

    public function unlike($id)
    {
        /** @var User $auth */
        $auth = auth()->user();
        /** @var Portfolio $portfolio */
        $portfolio = $this->findOneOrFail($id);
        $auth->unlike($portfolio);
        return true;
    }
}
