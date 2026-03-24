<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\Request;
use Karabin\Fabriq\Data\FileData;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class FileUploadController extends Controller
{
    public function store(Request $request): Response
    {
        $file = Fabriq::getModelClass('file');
        $file->save();
        try {
            $file->saveMedia($request->has('url'));
        } catch (\Throwable $exception) {
            $file->delete();
            throw $exception;
        }

        return FileData::fromModel($file)
            ->toResponse($request)
            ->setStatusCode(200);
    }
}
