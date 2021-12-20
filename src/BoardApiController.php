<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use XeFrontend;
use XePresenter;
use App\Http\Controllers\Controller as BaseController;
use Xpressengine\Plugins\Comment\Models\Comment;
use Xpressengine\Plugins\Comment\Models\Target;

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
        $query = Target::with('comment')->orderBy('head', 'desc')
            ->orderBy('created_at', 'asc')
            ->where('display', '!=', Comment::DISPLAY_HIDDEN);

        $target_ids = $request->get('target_ids');
        if($target_ids != null){
            $query->whereIn('target_id',json_dec($target_ids));
        }else{
            $query->where('target_id',$request->get('target_id'));
        }

        $comments = $query->get();

        // 댓글 총 수
        $totalCount = $query->count();

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
