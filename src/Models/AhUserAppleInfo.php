<?php
namespace Amuz\XePlugin\ApplicationHelper\Models;

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
class AhUserAppleInfo extends DynamicModel
{
    protected $table = 'ah_user_apple_info';

    protected $primaryKey = 'id';

    protected $guarded = [];

    public $incrementing = false;

    public $timestamps = true;
}
