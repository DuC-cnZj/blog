<?php

namespace App\Http\Controllers;

use App\Article;
use App\Comment;
use Illuminate\Http\Request;
use App\Http\Resources\CommentResource;

/**
 * Class CommentController
 * @package App\Http\Controllers
 */
class CommentController extends Controller
{
    /**
     * @param         $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @author duc <1025434218@qq.com>
     */
    public function index($id, Request $request)
    {
        $comments = Comment::query()
            ->where('article_id', $id)
            ->get([
                'visitor',
                'content',
                'comment_id',
                'created_at',
                'id',
                'userable_id',
                'userable_type',
            ]);

        return response()->json([
            'data' => array_reverse(
                $this->recursiveReplies(CommentResource::collection($comments)->toArray($request))
            ),
        ], 200);
    }

    /**
     * @param Request $request
     * @param int $id
     * @return CommentResource
     *
     * @author duc <1025434218@qq.com>
     */
    public function store(Request $request, int $id)
    {
        $article = Article::visible()->findOrFail($id);
        $content = $request->input('content');
        $parsedown = new \Parsedown();
        $htmlContent = $parsedown->text($content);
        $user = get_auth_user();

        /** @var Article $article */
        $comment = $article->comments()->create([
            'visitor'          => $request->ip(),
            'content'          => $htmlContent,
            'comment_id'       => $request->input('comment_id', 0),
            'userable_id'      => is_null($user) ? 0 : $user->id,
            'userable_type'    => is_null($user) ? '' : get_class($user),
        ]);
        $comment->unsetRelation('article');

        return (new CommentResource($comment->loadMissing('userable')))
            ->additional([
                'data' => [
                    'replies' => [],
                ],
            ]);
    }

    /**
     * @param array $comments
     * @param int $pid
     * @return array
     *
     * @author duc <1025434218@qq.com>
     */
    private function recursiveReplies(array $comments, $pid = 0)
    {
        $arr = [];
        foreach ($comments as $item) {
            if ((int) $item['comment_id'] === $pid) {
                $data = $this->recursiveReplies($comments, $item['id']);
                $item['replies'] = $data;
                $arr[] = $item;
            }
        }

        return $arr;
    }
}
