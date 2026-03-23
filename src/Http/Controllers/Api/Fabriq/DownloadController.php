<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DownloadController extends Controller
{
    protected static function downloadableTypes(): array
    {
        return [
            'images' => Fabriq::getFqnModel('image'),
            'files' => Fabriq::getFqnModel('file'),
            'videos' => Fabriq::getFqnModel('video'),
        ];
    }

    public function index(Request $request): BinaryFileResponse
    {
        $type = self::downloadableTypes()[$request->type];
        $files = $type::whereIn('id', $request->items)->get();

        $zip = new ZipArchive;
        $tempFile = tempnam(sys_get_temp_dir(), 'zip');
        $filename = 'fabriq-cms-export'.'-'.now()->format('Y-m-d-his').'.zip';

        $zip->open((string) $tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $files->each(function ($item) use ($zip, $request) {
            $mediaFile = $item->getFirstMedia($request->type);
            $disk = $item->getFirstMedia($request->type)->disk;
            if ($disk === 's3') {
                /** @var FilesystemAdapter $storage */
                $storage = Storage::disk($disk);
                $url = $storage->url($mediaFile->getPath());
                $file = file_get_contents($url);
                $zip->addFromString($item->media[0]->file_name, (string) $file);
            } else {
                $zip->addFile($item->getFirstMedia($request->type)->getPath(), $item->media[0]->file_name);
            }
        });

        $zip->close();
        $path = Storage::putFileAs('downloads', (string) $tempFile, $filename);
        unlink((string) $tempFile);

        $headers = [
            'X-FILENAME' => $filename,
        ];

        return response()->download(storage_path('app/'.$path), $filename, $headers)
            ->deleteFileAfterSend();
    }

    /**
     * Undocumented function.
     *
     * @return mixed BinaryFileResponse | StreamedResponse
     */
    public function show(Request $request, int $id)
    {
        $type = self::downloadableTypes()[$request->type];
        $item = $type::where('id', $id)->firstOrFail();
        $conversion = '';
        $mediaFile = $item->getFirstMedia($request->type);
        $name = $mediaFile->file_name;

        if ($request->has('webp')) {
            $conversion = 'webp';
            $name = pathinfo($name, PATHINFO_BASENAME).'.webp';
        }

        $disk = $mediaFile->disk;
        $headers = [
            'X-FILENAME' => $name,
        ];

        if ($disk === 'public') {
            return response()->download($mediaFile->getPath($conversion), $name, $headers);
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($disk);

        return $storage->download($mediaFile->getPath($conversion), $name, $headers);
    }
}
