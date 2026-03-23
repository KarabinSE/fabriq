<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaDownloadController extends Controller
{
    public function show(Request $request, string $uuid): BinaryFileResponse|StreamedResponse
    {
        $mediaFile = Fabriq::getModelClass('media')
            ->whereUuid($uuid)->firstOrFail();

        $name = $mediaFile->file_name;

        $disk = $mediaFile->disk;
        $headers = [
            'X-FILENAME' => $name,
        ];

        if ($disk === 'public') {
            return response()->download($mediaFile->getPath(), $name, $headers);
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($disk);

        return $storage->download($mediaFile->getPath(), $name, $headers);
    }
}
