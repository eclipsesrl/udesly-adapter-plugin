<?php
/**
 * Udesly Adapter Plugin
 *
 * @package     Udesly Adapter Plugin
 * @author      Udesly
 * @copyright   Udesly
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Udesly Adapter
 * Plugin URI:        https://www.udesly.com
 * Description:       This is a support plugin for Udesly (Webflow to WordPress converter) that allows you to enable additional features for your theme.
 * Version:           2.0.5
 * Author:            Udesly
 * Author URI:        https://www.udesly.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:    udesly-adapter
 * Domain Path:       /languages
 */


// Security Check
if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

// Constants
defined('UDESLY_ADAPTER_PLUGIN_DIRECTORY_PATH') ?: define('UDESLY_ADAPTER_PLUGIN_DIRECTORY_PATH', plugin_dir_path(__FILE__));
defined('UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL') ?: define('UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL', plugin_dir_url(__FILE__));
defined('UDESLY_ADAPTER_VERSION') ?: define('UDESLY_ADAPTER_VERSION', "2.0.5");
defined('UDESLY_TEXT_DOMAIN') ?: define('UDESLY_TEXT_DOMAIN', "udesly-adapter-plugin");

defined('UDESLY_ADAPTER_PLUGIN_MISC_PATH') ?: define('UDESLY_ADAPTER_PLUGIN_MISC_PATH', plugin_dir_path(__FILE__) . 'includes/misc/');
// Autoload vendor
require plugin_dir_path(__FILE__) . 'vendor/autoload.php';

/**
 * Activates udesly plugin
 */
function activate_udesly_plugin() {
    \Udesly\Core\Activator::activate_plugin();
}

/**
 * Deactivates Udesly Plugin
 */
function deactivate_udesly_plugin() {
    \Udesly\Core\Deactivator::deactivate_plugin();
}

// activation hook
register_activation_hook( __FILE__, 'activate_udesly_plugin' );

// deactivation hook
register_deactivation_hook( __FILE__, 'deactivate_udesly_plugin' );


function init_udesly_plugin() {
    $plugin = new \Udesly\Core\Udesly();
    $plugin->run();
}

if (\Udesly\Theme\DataManager::is_udesly_theme_active()) {
    init_udesly_plugin();
}

require 'plugin-update-checker/plugin-update-checker.php';
$update_checker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/eclipsesrl/udesly-adapter-plugin/',
    __FILE__,
    'udesly-adapter-plugin'
);
