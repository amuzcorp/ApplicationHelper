<?php
namespace Amuz\XePlugin\ApplicationHelper\Skins\DefaultAuth;

use Xpressengine\Skin\GenericSkin;

class DefaultAuth extends GenericSkin
{
    protected static $path = 'ApplicationHelper/src/Skins/DefaultAuth/views';

    /**
     * @var array
     */
    protected static $info = [];

    /**
     * @var string
     */
    protected static $viewDir = '';

    /**
     * Get the evaluated contents of the object.
     *
     * @return string
     */
    public function render()
    {
        $this->loadAssets();

        return parent::render();
    }

    /**
     * Show the confirm view for register.
     *
     * @return \Illuminate\View\View
     */
    protected function registerIndex()
    {
        app('xe.frontend')->js(
            [
                'assets/core/xe-ui-component/js/xe-page.js',
                'assets/core/xe-ui-component/js/xe-form.js'
            ]
        )->load();
        return $this->renderBlade('register.index');
    }

    /**
     * Show the view for register.
     *
     * @return \Illuminate\View\View
     */
    protected function registerCreate()
    {
        app('xe.frontend')->js(
            [
                'assets/core/xe-ui-component/js/xe-page.js',
                'assets/core/xe-ui-component/js/xe-form.js'
            ]
        )->load();
        return $this->renderBlade('register.create');
    }

    /**
     * Load assets.
     *
     * @return void
     */
    protected function loadAssets()
    {
        app('xe.frontend')->css(
            [
                'assets/core/xe-ui-component/xe-ui-component.css',
                'assets/core/user/auth.css'
            ]
        )->load();
    }
}
