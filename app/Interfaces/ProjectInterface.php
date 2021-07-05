<?php


namespace App\Interfaces;

use App\Http\Requests\AcceptOrRejectChangePrice;
use App\Http\Requests\AcceptOrRejectProjectRequest;
use App\Http\Requests\AddProjectAttachmentRequest;
use App\Http\Requests\CancelProjectRequest;
use App\Http\Requests\ChangeProjectRequest;
use App\Http\Requests\RateFreelancerRequest;
use App\Http\Requests\StoreSecurePaymentRequest;
use App\Models\ChangeProjectRequest as ChangePrice;
use App\Models\Project;
use App\Models\SecurePayment;

/**
 * Interface ProjectInterface
 * @package App\Interfaces;
 */
interface ProjectInterface extends BaseInterface
{
    /**
     * Return all model rows
     * @param array $columns
     * @param string $orderBy
     * @param string $sortBy
     * @return mixed
     */
    public function all($columns = array('*'), $orderBy = 'desc', $sortBy = 'id');

    /**
     * Return all model rows by paginate
     * @param string[] $columns
     * @param string $orderBy
     * @param string $sortBy
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function allByPagination($columns = array('*') ,$orderBy = 'id', $sortBy = 'desc', $page = 1, $limit = 10);

    public function lasts();
    public function created();
    public function accepted();
    public function finished();
    public function started();

    public function finishProject(Project $project);
    public function projectPayments($id);
    public function projectCreatedPayments($id);
    public function addProjectPayments(StoreSecurePaymentRequest $request, $id);

    public function addAttachment(AddProjectAttachmentRequest $request, $id);
    public function destroyAttachment($id, $attachment_id);

    public function changePrice(ChangeProjectRequest $request, $id);
    public function acceptOrRejectProjectPayment(AcceptOrRejectChangePrice $changePriceRequest, SecurePayment $payment);
    public function cancelProjectPayment(SecurePayment $payment);
    public function payProjectPayment(SecurePayment $payment);

    public function sendCancelProjectRequest(Project $project);
    public function acceptCancelProjectRequest(AcceptOrRejectProjectRequest $request, Project $project);

    public function changePriceRequests($id);
    public function rateFreelancer(RateFreelancerRequest $request, Project $project);
}
