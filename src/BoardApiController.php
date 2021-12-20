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
            'instanceId' => 'required'
        ]);

        $instanceId = $request->get('instanceId');
        $page = $request->get('page') ?: 1;

        $plugin = app('xe.plugin.comment');
        $handler = $plugin->getHandler();

        $config = $handler->getConfig($instanceId);

        \Event::fire('xe.plugin.comment.retrieved', [$request]);

        $take = $request->get('perPage', $config['perPage']);

        //쿼리 시작
        $query = Comment::where('instance_id', $instanceId)
            ->orderBy('head', 'desc')
            ->orderBy('created_at', 'asc')
            ->where('display', '!=', Comment::DISPLAY_HIDDEN);

        if($request->get('target_ids') != null && $request->get('target_ids') != '') {

            $targetId = is_array($request->get('target_ids')) ? $request->get('target_ids') : json_dec($request->get('target_ids'));
            if (is_array($targetId)) {
                $targetCommentList = \DB::table('comment_target')->whereIn('target_id', $targetId)->pluck('doc_id');
            } else {
                $targetCommentList = \DB::table('comment_target')->where('target_id', $targetId)->pluck('doc_id');
            }

            if(count($targetCommentList) !== 0) {
                $query->whereIn('id', $targetCommentList);
            }
        }

        $comments = $query->paginate(30, ['*'], 'page', $page);

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
            'page' => (int) $page
        ]);

    }
}
