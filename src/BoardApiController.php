<?php
namespace Amuz\XePlugin\ApplicationHelper;

use Amuz\XePlugin\Adapfit\Models\AdapfitUserBlock;
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
        $blocked = $request->get('blocked' , 'N');

        $plugin = app('xe.plugin.comment');
        $handler = $plugin->getHandler();

        $config = $handler->getConfig($instanceId);

        \Event::fire('xe.plugin.comment.retrieved', [$request]);

        $take = $request->get('perPage', $config['perPage']);

        //쿼리 시작

        $query = Comment::Division($instanceId)->whereHas('target', function($query) use ($request) {
                $target_ids = $request->get('target_ids');
                if($target_ids != null){
                    $query->whereIn('target_id',json_dec($target_ids));
                } else {
                    $query->where('target_id',$request->get('target_id'));
                }
            })->with('target')->orderBy('head', 'desc')
            ->orderBy('created_at', 'asc')
            ->where('display', '!=', Comment::DISPLAY_HIDDEN);

        if($blocked === 'Y') {
            $user_id = $request->get('login_id', '');
            if($user_id === '') return $query;

            $target_list = AdapfitUserBlock::where('user_id', $user_id)->pluck('target_user_id');
            if(count($target_list->toArray()) > 0) {
                $query->whereNotIn('user_id', $target_list);
            }
        }

        $comments = $query->get();

        // 댓글 총 수
        $totalCount = $query->count();

        $index = 0;
        foreach ($comments as $comment) {
            $handler->bindUserVote($comment);
            $user = app('xe.user')->users()->where('id', $comment->user_id)->first();
            if($user) {
                $comment->writer_profile = app('xe.user')->users()->where('id', $comment->user_id)->first()->getProfileImage();
            } else {
                $comment->writer_profile = null;
            }
            $index++;
        }

        return XePresenter::makeApi([
            'totalCount' => $totalCount,
            'hasMore' => false,
            'items' => $comments,
            'page' => (int) $page
        ]);

    }
}
