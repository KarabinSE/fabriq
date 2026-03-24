<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Data\VideoData;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class VideoUploadController extends Controller
{
    public function store(Request $request): Response
    {
        $video = Fabriq::getModelClass('video');
        $video->save();
        try {
            $video->addMediaFromRequest('video')
                ->toMediaCollection('videos');
        } catch (\Throwable $exception) {
            $video->delete();
            throw $exception;
        }

        return VideoData::fromModel($video)
            ->toResponse($request)
            ->setStatusCode(200);
    }
}
