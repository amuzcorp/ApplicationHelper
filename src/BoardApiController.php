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
            'targetType' => 'required',
            'targetId' => 'required',
            'instanceId' => 'required'
        ]);

        $offsetHead = !empty($request->get('offsetHead')) ? $request->get('offsetHead') : null;
        $offsetReply = !empty($request->get('offsetReply')) ? $request->get('offsetReply') : null;

        $targetType = $request->get('targetType');
        $targetId = $request->get('targetId');
        $instanceId = $request->get('instanceId');

        $plugin = app('xe.plugin.comment');
        $handler = $plugin->getHandler();

        $config = $handler->getConfig($instanceId);

        \Event::fire('xe.plugin.comment.retrieved', [$request]);

        $page = $request->get('page') ?: 1;
        $take = $request->get('perPage', $config['perPage']);

        $model = $handler->createModel($instanceId);
        $query = $model->newQuery()->whereHas('target', function ($query) use ($targetId, $targetType) {
            $query->where('target_id', $targetId)->where('target_type', $targetType);
        })
//            ->where('approved', Comment::APPROVED_APPROVED)
            ->where('display', '!=', Comment::DISPLAY_HIDDEN);

        // 댓글 총 수
        $totalCount = $query->count();

        $direction = $config->get('reverse') === true ? 'asc' : 'desc';

        if ($offsetHead !== null) {
            $query->where(function ($query) use ($offsetHead, $offsetReply, $direction) {
                $query->where('head', $offsetHead);
                $operator = $direction == 'desc' ? '<' : '>';
                $offsetReply = $offsetReply ?: '';

                $query->where('reply', $operator, $offsetReply);
                $query->orWhere('head', '<', $offsetHead);
            });
        }
        $query->orderBy('head', 'desc')->orderBy('reply', $direction)->take($take + 1);
        // 대상글의 작성자까지 eager load 로 조회하여야 되나
        // 대상글 작성자를 조회하는 relation 명을 지정할 수 없음.
        $comments = $query->with('target.commentable')->paginate($take, ['*'], 'page', $page);
        foreach ($comments as $comment) {
            $handler->bindUserVote($comment);
        }

        return XePresenter::makeApi([
            'totalCount' => $totalCount,
            'items' => $comments,
        ]);

    }
}
