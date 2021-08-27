<?php
namespace Amuz\XePlugin\ApplicationHelper\Migrations;

use Illuminate\Database\Schema\Blueprint;
use DB;
use Schema;

/**
 * Class UserMigration
 *
 * @category    Migrations
 * @package     Xpressengine\Migrations
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2020 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class Migration
{

    private $table = 'ah_user_token';
    /**
     * @return void
     */
    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->engine = "InnoDB";

            $table->string('token', 36)->nullable()->comment('token for keep login');
            $table->string('user_id', 36)->comment('user ID');

            $table->string('device_name', 36);
            $table->string('device_version', 36);
            $table->string('device_id', 36);

            $table->timestamp('created_at')->nullable()->index()->comment('created date');
            $table->timestamp('updated_at')->nullable()->index()->comment('updated date');

            $table->index('device_name');
            $table->index('device_version');
            $table->index('device_id');
            $table->primary('token');
        });
    }


    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }

    public function tableExists()
    {
        return Schema::hasTable($this->table);
    }

}
