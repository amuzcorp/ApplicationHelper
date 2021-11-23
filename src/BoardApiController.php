<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Illuminate\Http\Request;
use XeFrontend;
use XePresenter;
use App\Http\Controllers\Controller as BaseController;
use Xpressengine\Plugins\Comment\Models\Comment;

class BoardApiController extends BaseController
{

    public function getItem(Request $request) {

        $this->validate($request, [
            'targetId' => 'required',
            'instanceId' => 'required'
        ]);

        $targetId = $request->get('targetId');
        $instanceId = $request->get('instanceId');
        $page = $request->get('page') ?: 1;

        $plugin = app('xe.plugin.comment');
        $handler = $plugin->getHandler();

        $config = $handler->getConfig($instanceId);

        \Event::fire('xe.plugin.comment.retrieved', [$request]);

        $take = $request->get('perPage', $config['perPage']);

        $targetCommentList = \DB::table('comment_target')->where('target_id', $targetId)->pluck('doc_id');
        $comments = Comment::whereIn('id', $targetCommentList)->where('instance_id', $instanceId)
            ->orderBy('head', 'asc')
            ->orderBy('created_at', 'asc')
            ->where('display', '!=', Comment::DISPLAY_HIDDEN)
            ->paginate($take + 1, ['*'], 'page', $page);

        // 댓글 총 수
        $totalCount = $comments->total();

        foreach ($comments as $comment) {
            $handler->bindUserVote($comment);
            $comment->writer_profile = app('xe.user')->users()->where('id', $comment->user_id)->first()->getProfileImage();
        }

        return XePresenter::makeApi([
            'totalCount' => $totalCount,
            'hasMore' => $comments->hasMorePages(),
            'items' => $comments,
            'page' => $page
        ]);

    }
}
