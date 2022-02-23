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
                unset($comments[$index]);
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
