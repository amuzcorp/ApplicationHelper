<?php
namespace Amuz\Plugin\ApplicationHelper\Models;

use Illuminate\Support\Arr;
use Xpressengine\Database\Eloquent\DynamicModel;

/**
 * Group
 *
 * @category    Widget
 * @package     Xpressengine\Plugins\Banner
 * @author      XE Team (developers) <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class AhUserToken extends DynamicModel
{
    protected $table = 'ah_user_token';

    protected $primaryKey = 'token';

    public $incrementing = false;

    public $timestamps = true;
}
