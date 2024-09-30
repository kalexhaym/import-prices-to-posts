<?php
/*
 * Plugin Name: Import Prices To Posts
 * Description: Add import prices functionality.
 * Author: Aleksei Kovalenko
 * Author URI: https://github.com/kalexhaym
 * Version: 1.0.0
 */

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

define('IPRICES_VERSION', '1.0.0');

define('IPRICES_UPDATES_TABLE_NAME', 'iprices_updates');
define('IPRICES_PRICES_TABLE_NAME', 'iprices');

define('IPRICES_STATUS_NOT_FOUND', 0);
define('IPRICES_STATUS_NO_CHANGES', 1);
define('IPRICES_STATUS_PENDING_UPDATE', 2);

add_action('admin_enqueue_scripts', 'iprices_enqueue_admin_styles');
add_action('admin_menu', 'iprices_menu_option');

/**
 * @return void
 */
function iprices_enqueue_admin_styles(): void
{
    wp_enqueue_style('iprices-styles', plugin_dir_url( __FILE__ ) . 'css/styles.css', false, IPRICES_VERSION);
}

/**
 * @return void
 */
function iprices_menu_option(): void
{
    add_menu_page(
        'Import Prices',
        'Import Prices',
        'manage_options',
        'iprices',
        'iprices_admin_page',
        'dashicons-database-import',
        40
    );
}

/**
 * @return void
 */
function iprices_admin_page(): void
{
    require_once plugin_dir_path(__FILE__) . 'admin/view.php';
}

/**
 * @return void
 */
function iprices_activation_hook(): void
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $db_table_name = $wpdb->prefix . IPRICES_UPDATES_TABLE_NAME;

    if($wpdb->get_var("show tables like '$db_table_name'") != $db_table_name)
    {
        $sql = "CREATE TABLE {$db_table_name} (
                id int(11) NOT NULL auto_increment,
                post_id int(11) NOT NULL,
                old_price float(8,2) NOT NULL,
                new_price float(8,2) NOT NULL,
                status int(1) NOT NULL,
                UNIQUE KEY id (id)
                ) {$charset_collate};";

        $wpdb->query($sql);
    }

    $db_table_name = $wpdb->prefix . IPRICES_PRICES_TABLE_NAME;

    if($wpdb->get_var("show tables like '$db_table_name'") != $db_table_name)
    {
        $sql = "CREATE TABLE {$db_table_name} (
                id int(11) NOT NULL auto_increment,
                post_id int(11),
                price float(8,2) NOT NULL,
                UNIQUE KEY id (id)
                ) {$charset_collate};";

        $wpdb->query($sql);
    }
}

/**
 * @return void
 */
function iprices_deactivation_hook(): void
{
    global $wpdb;

    $tables = [
        IPRICES_UPDATES_TABLE_NAME,
        IPRICES_PRICES_TABLE_NAME,
    ];

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{$table};");
    }
}

register_activation_hook(
    __FILE__,
    'iprices_activation_hook'
);

register_deactivation_hook(
    __FILE__,
    'iprices_deactivation_hook'
);

/**
 * @return void
 */
function iprices_download_action(): void
{
    require_once plugin_dir_path(__FILE__) . 'admin/download.php';
}

add_action('rest_api_init', function($server) {
    $server->register_route('iprices', '/iprices/download', array(
        'methods'  => 'GET',
        'callback' => 'iprices_download_action',
    ));
});