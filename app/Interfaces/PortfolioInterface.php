<?php


namespace App\Interfaces;

use App\Http\Requests\AddPortfolioImageRequest;
use Illuminate\Http\Request;

/**
 * Interface PortfolioInterface
 * @package App\Interfaces;
 */
interface PortfolioInterface extends BaseInterface
{
    public function addImage(AddPortfolioImageRequest $request, $id);
    public function destroyImage($id, $image_id);

}
