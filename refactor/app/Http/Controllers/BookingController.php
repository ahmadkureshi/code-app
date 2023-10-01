<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $user = $request->__authenticatedUser;

        if ($user_id = $request->get('user_id')) {
            $response = $this->repository->getUsersJobs($user_id);
        } elseif ($user->isAdmin() || $user->isSuperAdmin()) {
            $response = $this->repository->getAll($request);
        }else {
            return response('Unauthorized.', 401);
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        if(!$job) {
            return response('Job not found', 404);
        }

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->store($request->__authenticatedUser, $data);

        return response($response);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, UpdateJobRequest $request)
    {
        $data = $request->all();
        $cuser = $request->__authenticatedUser;
        $response = $this->repository->updateJob($id, $data, $cuser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        // $adminSenderEmail = config('app.adminemail');
        // above email is not used anywhere in this function
        $data = $request->all();
        $response = $this->repository->storeJobEmail($data);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if ($user_id = $request->get('user_id')) {
            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;
        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;
        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;
        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->endJob($data);

        return response($response);
    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->customerNotCall($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        // $data = $request->all(); not using this variable in this function
        $user = $request->__authenticatedUser;
        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        $jobid = $data['jobid'] ?? null;

        $updateData = [
            'distance' => $data['distance'] ?? null,
            'time' => $data['time'] ?? null,
            'session_time' => $data['session_time'] ?? null,
            'admin_comments' => $data['admincomment'] ?? null,
            'flagged' => $data['flagged'] == 'true' ? 'yes' : 'no',
            'manually_handled' => $data['manually_handled'] == 'true' ? 'yes' : 'no',
            'by_admin' => $data['by_admin'] == 'true' ? 'yes' : 'no',
        ];

        if ($updateData['flagged'] == 'yes' && empty($updateData['admin_comments'])) {
            return "Please, add comment";
        }

        $affectedRows = Distance::where('job_id', '=', $jobid)->update($updateData);

        if ($affectedRows > 0) {
            return response('Record updated!');
        } else {
            return response('Record not found', 404);
        }
    }
    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    public function resendNotifications(Request $request)
    {

        try{
            $data = $request->all();
            $job = $this->repository->find($data['jobid']);
            $job_data = $this->repository->jobToData($job);
            $this->repository->sendNotificationToTranslator($job, $job_data, '*');

            return response(['success' => 'Push sent']);
        }catch (\Exception $e){
            return response(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
