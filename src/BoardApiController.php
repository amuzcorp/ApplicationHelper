<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Illuminate\Database\Query\JoinClause;
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
        $query = Comment::where('instance_id', $instanceId);

        $query->leftJoin('comment_target',
            function (JoinClause $join) {
                $join->on('documents.id','=','comment_target.target_id');
            }
        );

        $query->orderBy('head', 'desc')
            ->orderBy('created_at', 'asc')
            ->where('display', '!=', Comment::DISPLAY_HIDDEN);

        $target_ids = $request->get('target_ids');
        if($target_ids != null){
            $query->whereIn('comment_target.target_id',json_dec($target_ids));
        }else{
            $query->where('comment_target.target_id',$request->get('target_id'));
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
