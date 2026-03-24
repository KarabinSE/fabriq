<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Karabin\Fabriq\Fabriq;
use Karabin\Fabriq\Http\Controllers\Controller;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ImageSourceSetController extends Controller
{
    /**
     * Get src set data.
     */
    public function show(int $id): JsonResponse
    {
        $image = Fabriq::getFqnModel('image')::whereId($id)->firstOrFail();

        /** @var Media $media */
        $media = $image->media->first();

        $conversion = '';
        $attributeString = '';
        $loadingAttributeValue = '';
        $width = ($media->responsiveImages()->files->first()) ? $media->responsiveImages()->files->first()->width() : null;
        $height = ($media->responsiveImages()->files->first()) ? $media->responsiveImages()->files->first()->height() : null;

        /** @var view-string $viewString * */
        $viewString = 'vendor.fabriq._partials.srcset';
        $srcset = view($viewString, compact(
            'media',
            'conversion',
            'attributeString',
            'loadingAttributeValue',
            'width',
            'height',
        ))->render();

        return response()->json([
            'data' => [
                'html' => $srcset,
                'srcset' => $media->getSrcset(''),
                'onload' => "window.requestAnimationFrame(function(){if(!(size=getBoundingClientRect().width))return;onload=null;sizes=Math.ceil(size/window.innerWidth*100)+'vw';});",
                'sizes' => '1px',
                'src' => $media->getUrl(),
                'width' => $width,
                'height' => $height,
                'alt_text' => $image->alt_text,
            ],
        ]);
    }
}
