<?php
namespace Flamix\Bitrix24\WooCommerce;

class Settings {

    /**
     * Get Plugin UNIQ Name to Setting
     *
     * @return string
     */
    public static function getPluginName()
    {
        return strtolower(str_replace('\\', '_', __NAMESPACE__));
    }

    /**
     * Get Full Options Name
     *
     * @param $name
     * @return string
     */
    public static function getOptionName($name)
    {
        return self::getPluginName() . '_' . $name;
    }

    /**
     * Get Options VALUE by Name
     *
     * @param $name
     * @return mixed|void
     */
    public static function getOption($name)
    {
        return get_option(self::getOptionName($name));
    }

    /**
     * Register Setting Page in Menu
     */
    public static function add_menu()
    {
        add_options_page(
            'Bitrix24 and WordPress WooCommerce integrations',
            'Bitrix24 ← WooCommerce',
            'administrator',
            __FILE__,
            [__NAMESPACE__ . '\Settings', 'include_setting_page']
        );

        add_action('admin_init', [__NAMESPACE__ . '\Settings', 'register_options']);
    }

    /**
     * Register options
     */
    public static function register_options()
    {
        register_setting(self::getOptionName('group'), self::getOptionName('lead_domain'), [__NAMESPACE__ . '\Helpers', 'parseDomain']);
        register_setting(self::getOptionName('group'), self::getOptionName('lead_api'));
        register_setting(self::getOptionName('group'), self::getOptionName('lead_backup_email'), [__NAMESPACE__ . '\Helpers', 'isEmail']);
        register_setting(self::getOptionName('group'), self::getOptionName('product_find_by'));
        register_setting(self::getOptionName('group'), self::getOptionName('product_find_by_bitrix24_attribute'));
        register_setting(self::getOptionName('group'), self::getOptionName('product_find_by_woocommerce_attribute'));
    }

    /**
     * Include page
     */
    public static function include_setting_page() {
        include_once FLAMIX_BITRIX24_WOOCOMMERCE_ORDER_DIR . '/resources/views/index.php';
    }

    /**
     * Add link to Setting Page and Install Module Landing
     * @param $links
     * @return mixed
     */
    public static function add_link_to_plugin_widget($links)
    {
        $url = esc_url(add_query_arg(
            'page',
            'flamix-bitrix24-and-woo-integrations/settings/Settings.php',
            get_admin_url() . 'options-general.php'
        ));

        $settings_link  = '<a href="' . $url . '">' . __( 'Settings' ) . '</a>';
        $plugin_link    = '<a target="_blank" href="https://flamix.solutions/bitrix24/integrations/site/woocommerce.php">Bitrix24 Plugin</a>';

        array_push(
            $links,
            $settings_link,
            $plugin_link
        );

        return $links;
    }
}