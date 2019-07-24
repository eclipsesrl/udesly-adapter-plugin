<?php

namespace Udesly\Dashboard;

use Udesly\Boxes\Box;
use Udesly\Query\Posts;
use Udesly\Query\Taxonomies;
use Udesly\Rules\Rule;
if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
 * Class Menu
 * @package Udesly\Dashboard
 */
class Menu
{

    const MENU_SLUG = "udesly_adapter_dashboard";
    const ICON = "data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDIzLjAuNCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4IgoJIHZpZXdCb3g9IjAgMCAxNiAxNiIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgMTYgMTY7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4KCS5zdDB7ZmlsbDojRkZGRkZGO30KPC9zdHlsZT4KPHBhdGggY2xhc3M9InN0MCIgZD0iTTE0LjEsOC45YzAsMC4zLDAsMC41LTAuMSwwLjhsMCwwbC0wLjEsMC4ybDAsMHYwYy0wLjEsMC4zLTAuMywwLjYtMC41LDAuOWwwLDBjLTEuMSwxLjYtMi42LDItMy40LDIuMWwwLDAKCWMtMC4yLDAtMC4zLDAtMC40LDBoMGwwLDBIOS41bC0wLjEsMGgwbDAsMGwwLDBoMGMtMC4xLDAtMC4yLDAtMC4zLTAuMWMtMC4xLDAtMC4yLTAuMS0wLjMtMC4xaDBjLTAuMS0wLjEtMC4yLTAuMi0wLjItMC4zCgljLTAuMi0wLjMtMC4zLTAuNi0wLjMtMC45YzAtMC4xLDAtMC4yLTAuMS0wLjRjMCwwLDAsMCwwLDBsMCwwdjBsMCwwbDAsMGMwLDAuMS0wLjEsMC4xLTAuMSwwLjJjMCwwLjEtMC4xLDAuMS0wLjEsMC4ybDAsMAoJYzAsMC4xLTAuMSwwLjEtMC4xLDAuMmMwLDAsMCwwLDAsMGMwLDAsMCwwLjEtMC4xLDAuMWMtMC4xLDAuMS0wLjEsMC4yLTAuMiwwLjJjMCwwLTAuMSwwLjEtMC4xLDAuMWMwLDAtMC4xLDAuMS0wLjEsMC4xCgljLTAuMSwwLTAuMSwwLjEtMC4yLDAuMWMtMC4xLDAtMC4xLDAuMS0wLjIsMC4xYy0wLjUsMC40LTEsMC41LTEuNiwwLjVINS4yYy0xLDAtMS44LTAuMi0yLjQtMC42Yy0wLjYtMC40LTAuOS0xLTAuOS0xLjgKCWMwLTAuMiwwLTAuNCwwLjEtMC42bDAsMGMwLDAsMC0wLjEsMC0wLjFjMC44LTIuMiwxLTQuNSwxLjEtNS45bDAsMGwwLTAuMWMwLTAuMiwwLjEtMC4zLDAuMy0wLjRDMy44LDMuMSw0LjQsMyw1LDMKCWMwLjYsMCwxLDAuNCwxLjEsMS4yQzYuMSw0LjksNi4xLDUuNSw2LDZTNS43LDcuMiw1LjUsOGMtMC4yLDAuOC0wLjMsMS41LTAuMywyLjFjMCwwLjUsMC4zLDAuNywwLjgsMC43YzAsMCwwLjEsMCwwLjEsMAoJYzAuNywwLDEuMi0wLjYsMS43LTEuOGMwLjQtMC45LDAuNi0xLjksMC44LTNjMCwwLDAsMCwwLDBsMCwwYzAtMC4xLDAtMC4yLDAtMC40YzAtMC4yLDAtMC4zLDAtMC41YzAtMC4yLDAtMC4zLDAtMC41VjQuMwoJYzAtMC45LDAuNy0xLjQsMi4xLTEuNGMwLjQsMCwwLjYsMC4xLDAuNywwLjNjMC4xLDAuMiwwLjEsMC42LDAuMSwxLjFjMCwwLjctMC4yLDEuNy0wLjUsMy4xYy0wLjEsMC41LTAuMiwxLTAuMywxLjRjMCwwLDAsMCwwLDAKCWMwLDAuMSwwLDAuMSwwLDAuMnYwYy0wLjEsMC41LTAuMSwxLTAuMSwxLjJjMCwwLjIsMC4xLDAuMywwLjIsMC4zYzAuMiwwLDAuNS0wLjEsMC44LTAuNGMwLDAsMCwwLDAsMGwwLDBjMC4zLTAuMiwwLjUtMC41LDAuOC0wLjgKCWMwLjMtMC4zLDAuNi0wLjYsMC44LTAuOGMwLjMtMC4zLDAuNC0wLjQsMC41LTAuNEMxNCw4LjIsMTQuMSw4LjQsMTQuMSw4Ljl6Ii8+Cjwvc3ZnPgo=
";
    /**
     * Registers menu pages
     * @see 'admin_menu'
     */
    public static function register_menu_pages()
    {
        add_menu_page(__('Udesly Dashboard', UDESLY_TEXT_DOMAIN), __('Udesly', UDESLY_TEXT_DOMAIN), 'manage_options', self::MENU_SLUG, '', self::ICON, 65);
        add_submenu_page(self::MENU_SLUG, __('Welcome', UDESLY_TEXT_DOMAIN), __('Welcome', UDESLY_TEXT_DOMAIN), 'manage_options', self::MENU_SLUG, function() {
            include __DIR__ . "/Views/Dashboard.php";
        });

        add_submenu_page(self::MENU_SLUG, __('Posts Queries', UDESLY_TEXT_DOMAIN), __('Posts Queries', UDESLY_TEXT_DOMAIN), 'manage_options', 'edit.php?post_type='. Posts::TYPE_NAME, '');
        add_submenu_page(self::MENU_SLUG, __('Term Queries', UDESLY_TEXT_DOMAIN), __('Term Queries', UDESLY_TEXT_DOMAIN), 'manage_options', 'edit.php?post_type='. Taxonomies::TYPE_NAME, '');
        add_submenu_page(self::MENU_SLUG, __('Rules', UDESLY_TEXT_DOMAIN), __('Rules', UDESLY_TEXT_DOMAIN), 'manage_options', 'edit.php?post_type='. Rule::TYPE_NAME, '');

        add_submenu_page(self::MENU_SLUG, __('Boxes', UDESLY_TEXT_DOMAIN), __('Boxes', UDESLY_TEXT_DOMAIN), 'manage_options', 'edit.php?post_type='. Box::TYPE_NAME, '');
        add_submenu_page( self::MENU_SLUG, __('Custom Post Types', UDESLY_TEXT_DOMAIN), __('Custom Post Types', UDESLY_TEXT_DOMAIN), 'manage_options', self::MENU_SLUG . '_cpt', '\Udesly\Dashboard\Views\CustomPostTypes::show');

        add_submenu_page( self::MENU_SLUG, __('Settings', UDESLY_TEXT_DOMAIN), __('Settings', UDESLY_TEXT_DOMAIN), 'manage_options', self::MENU_SLUG . '_settings', '\Udesly\Dashboard\Views\Settings::show');
        add_submenu_page( self::MENU_SLUG, __('Webflow Data', UDESLY_TEXT_DOMAIN), __('Webflow Data', UDESLY_TEXT_DOMAIN), 'manage_options', self::MENU_SLUG . '_data', '\Udesly\Dashboard\Views\WebflowData::show');

    }

    public static function admin_hooks() {
        add_action('admin_menu', 'Udesly\Dashboard\Menu::register_menu_pages');
    }


}