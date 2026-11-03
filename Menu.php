<?php

namespace Piwik\Plugins\IP2Location;

use Piwik\Menu\MenuAdmin;
use Piwik\Piwik;

class Menu extends \Piwik\Plugin\Menu
{
    /**
     * Adds the plugin to the administration menu.
     *
     * @param MenuAdmin $menu
     */
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Piwik::isUserHasSomeAdminAccess()) {
            $menu->addSystemItem(
                'IP2Location',
                ['module' => 'IP2Location', 'action' => 'config'],
                35
            );
        }
    }
}
