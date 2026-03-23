<?php

namespace Karabin\Fabriq\Http\Controllers\Api\Fabriq;

use Illuminate\Http\JsonResponse;
use Karabin\Fabriq\Data\CommentData;
use Karabin\Fabriq\Enums\ApiResponseCode;
use Karabin\Fabriq\Http\Controllers\Controller;
use Karabin\Fabriq\Http\Requests\DeleteCommentRequest;
use Karabin\Fabriq\Http\Requests\UpdateCommentRequest;
use Karabin\Fabriq\Models\Comment;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
{
    public function update(UpdateCommentRequest $request, int $id): Response
    {
        $comment = Comment::findOrFail($id);
        $comment->comment = $request->comment;
        $comment->edited_at = now();
        $comment->save();

        return CommentData::fromModel($comment)->wrap('data')->toResponse($request);
    }

    public function destroy(DeleteCommentRequest $request, int $id): JsonResponse
    {
        $comment = Comment::whereId($id)
            ->with('notifications')
            ->firstOrFail();

        $comment->notifications->each(function ($item) {
            $item->delete();
        });

        $comment->delete();

        return response()->json([
            'code' => ApiResponseCode::Success->value,
            'http_code' => 200,
            'message' => 'Comment was deleted',
        ]);
    }
}
