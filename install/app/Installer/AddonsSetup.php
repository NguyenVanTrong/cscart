<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Installer;

use Tygh\Registry;
use Tygh\Addons\SchemesManager;

class AddonsSetup
{
    /**
     * Installs addons
     *
     * @param  bool  $install_demo
     * @param  array $addons       List of addons to be installed, if empty will be installed addons according <auto_install> tag
     * @return bool  Always true
     */
    public function setup($install_demo = true, $addons = array())
    {
        $app = App::instance();

        Registry::set('customer_theme_path', Registry::get('config.dir.install_themes') . '/' . App::THEME_NAME);

        $addons = empty($addons) ? $this->_getAddons() : $this->_sortByPriority($addons);

        foreach ($addons as $addon_name) {

            Registry::clearCachedKeyValues(); // reset cache before installing an add-on

            if (fn_install_addon($addon_name, false, $install_demo) ) {
                // load add-on after install to properly use its schemes
                if (SchemesManager::getScheme($addon_name)->getStatus() == 'A') {
                    fn_load_addon($addon_name);
                }
                fn_set_progress('echo', $app->t('addon_installed', array('addon' => $addon_name)) . '<br/>', true);
            }

            Registry::set('runtime.database.errors', '');
        }

        Registry::clearCachedKeyValues(); // reset cache after installing all the add-ons

        return true;
    }

    /**
     * Returns addons list that need be installed for some PRODUCT TYPE
     *
     * @param  string $product_type Product type
     * @return array  List af addons
     */
    private function _getAddons($product_type = PRODUCT_EDITION)
    {
        $available_addons = fn_get_dir_contents(Registry::get('config.dir.addons'), true, false);
        $addons_list = array();

        foreach ($available_addons as $addon_name) {
            $scheme = SchemesManager::getScheme($addon_name);

            if (!empty($scheme)) {
                $auto_install = $scheme->autoInstallFor();
                if (in_array($product_type, $auto_install)) {
                    $addons_list[] = $addon_name;
                }
            }
        }

        return $this->_sortByPriority($addons_list);
    }

    /**
     * Returns addons list that sorted by priority
     *
     * @param  array $addons List of addons
     * @return array List af addons
     */
    private function _sortByPriority($addons)
    {
        $addons_priority = array();
        foreach ($addons as $addon_name) {
            $scheme = SchemesManager::getScheme($addon_name);

            if (!empty($scheme)) {
                $addons_priority[$addon_name] = $scheme->getPriority();
            }
        }

        asort($addons_priority);

        return array_keys($addons_priority);
    }
}
