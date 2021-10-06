<?php
namespace Amuz\XePlugin\ApplicationHelper;

use App\Http\Controllers\Controller as BaseController;

class SettingsController extends BaseController
{

    public function __construct()
    {
    }

    public function index()
    {
        // output
        return \XePresenter::make('ApplicationHelper::views.settings.index', []);
    }

    public function navigator()
    {
        // output
        return \XePresenter::make('ApplicationHelper::views.settings.navigator', []);
    }
}
