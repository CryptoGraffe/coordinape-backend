<?php

namespace App\Http\Controllers;

use App\Http\Requests\CircleRequest;
use App\Http\Requests\CreateCircleRequest;
use App\Http\Requests\FileUploadRequest;
use App\Models\Circle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Repositories\CircleRepository;

class CircleController extends Controller
{
    protected $repo;
    public function __construct(CircleRepository $repo) {
        $this->repo = $repo;
    }

    public function getCircles(Request $request, $circle_id = null) : JsonResponse {
        return response()->json($this->repo->getCircles($request));
    }

    public function createCircle(CreateCircleRequest $request) : JsonResponse {
        return response()->json($this->repo->createCircle($request));
    }

    public function getWebhook(Request $request, $circle_id) : JsonResponse {
        return response()->json($this->repo->getWebhook($circle_id));
    }

    public function updateCircle(CircleRequest $request, $circle_id, Circle $circle) : JsonResponse
    {
        return response()->json($this->repo->updateCircle($circle, $request));
    }

    public function uploadCircleLogo(FileUploadRequest $request, $circle_id) : JsonResponse {

        $circle = $this->repo->uploadCircleLogo($request);
        if($circle)
            return response()->json($circle);

        return response()->json(['message'=> 'File Upload Failed' ,422]);
    }

    public function fullCircle(Request $request) {
        $circle_id = $request->get('circle_id');
//        dd($request->user()->users);
        if($circle_id) {
            $profile = $request->user();
            $user = $profile->users()->where('circle_id',$circle_id)->first();
            if($user) {
                $users = $user->circle->users;
                $user_id = $user->id;
                $users->load(['pendingSentGifts' => function ($q) {
                    $q->select(['id','recipient_id','sender_id','tokens','circle_id','epoch_id','created_at','updated_at']);
                },
                'pendingReceivedGifts' => function ($q) {
                    $q->select(['id','recipient_id','sender_id','tokens','circle_id','epoch_id','created_at','updated_at']);
                }
                ]);

                $user->load('pendingSentGifts')
                dd($users[0]);
            }
        }

    }
}
