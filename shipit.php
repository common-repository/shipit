<?php
/*
Plugin Name: Shipit
Description: Shipit Calculator Shipping couriers
Version:     9.3.0
Author:      Shipit
Author URI:  https://Shipit.cl/
License: GPLv2 or later

Shipit-calculator is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Shipit-calculator is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Shipit-calculator. If not, see {License URI}.
*/
//$directories = [
//  dirname(__FILE__) . '/src/',
//  dirname(__FILE__) . '/src/shipit_service/',
//  dirname(__FILE__) . '/src/includes/',
//  dirname(__FILE__) . '/src/includes/hooks/',
//  dirname(__FILE__) . '/src/includes/services/'
//];

//foreach ($directories as $directory) {
//  foreach (glob($directory . '*.php') as $file) {
//      require_once $file;
//  }
//}

require_once dirname(__FILE__) . '/src/class.settings-api.php';
require_once dirname(__FILE__) . '/src/shipit_service/http_client.php';
require_once dirname(__FILE__) . '/src/shipit_service/core.php';
require_once dirname(__FILE__) . '/src/shipit_service/opit.php';
require_once dirname(__FILE__) . '/src/shipit_service/integration.php';
require_once dirname(__FILE__) . '/src/shipit_service/order.php';
require_once dirname(__FILE__) . '/src/shipit_service/boxify.php';
require_once dirname(__FILE__) . '/src/shipit_service/address.php';
require_once dirname(__FILE__) . '/src/shipit_service/destiny.php';
require_once dirname(__FILE__) . '/src/shipit_service/courier.php';
require_once dirname(__FILE__) . '/src/shipit_service/price.php';
require_once dirname(__FILE__) . '/src/shipit_service/seller.php';
require_once dirname(__FILE__) . '/src/shipit_service/measure.php';
require_once dirname(__FILE__) . '/src/shipit_service/measure_collection.php';
require_once dirname(__FILE__) . '/src/shipit_service/payment.php';
require_once dirname(__FILE__) . '/src/shipit_service/insurance.php';
require_once dirname(__FILE__) . '/src/shipit_service/rate.php';
require_once dirname(__FILE__) . '/src/shipit_service/woocommerce_setting_helper.php';
require_once dirname(__FILE__) . '/src/shipit-settings.php';
require_once dirname(__FILE__) . '/src/webhook.php';
require_once dirname(__FILE__) . '/src/auther.php';
require_once dirname(__FILE__) . '/src/bulk_actions.php';
require_once dirname(__FILE__) . '/src/shipit_debug.php';
require_once dirname(__FILE__) . '/src/shipit_service/bugsnag.php';
require_once dirname(__FILE__) . '/src/shipit_service/emergency_rate.php';
require_once dirname(__FILE__) . '/src/shipit_service/setting.php';
require_once dirname(__FILE__) . '/src/shipit_service/cloud_function.php';
require_once dirname(__FILE__) . '/src/shipit_service/client_session.php';
require_once dirname(__FILE__) . '/src/shipit_service/commune.php';
require_once dirname(__FILE__) . '/src/shipit_service/shipit_country_helper.php';
require_once dirname(__FILE__) . '/src/includes/hooks/actions.php';
require_once dirname(__FILE__) . '/src/includes/hooks/filters.php';
require_once dirname(__FILE__) . '/src/includes/services/order.php';
require_once dirname(__FILE__) . '/src/includes/services/shipment.php';
require_once dirname(__FILE__) . '/src/includes/class-shipit-settings-admin.php';
require_once dirname(__FILE__) . '/src/includes/functions.php';

new Shipit_Settings_Admin();

function activate_shipit() {
    add_option('shipit_do_activation_redirect', true);
    add_option('shipit_user', '', '');
    add_option('shipit_token', '', '');
    add_option('shipit_account_country', '', '');
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shipit (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        package varchar(1000) NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    dbDelta($sql);

    $username = str_replace(' ', '_', get_bloginfo('name')) . '_shipit';
    $password = hash_password($username . '123');
    $userdata = array(
        'user_login' => $username,
        'nickname'   => 'Shipit',
        'user_email' => 'hola@shipit.cl',
        'user_url'   => get_site_url(),
        'user_pass'  => $password
    );

    $user_id = username_exists($username);
    if (!$user_id) {
        $user_id = wp_insert_user($userdata);
        if (is_wp_error($user_id)) {
            echo 'Error al crear el usuario: ' . $user_id->get_error_message();
        }
    } else {
        $userdata['ID'] = $user_id;
        $user_id = wp_update_user($userdata);
        if (is_wp_error($user_id)) {
            echo 'Error al actualizar el usuario: ' . $user_id->get_error_message();
        }
    }


  $sql_drop = " DROP TABLE IF EXISTS {$wpdb->prefix}user_shipit;";
  dbDelta($sql_drop);
  $user_shipit_table = " CREATE TABLE IF NOT EXISTS {$wpdb->prefix}user_shipit (
                        id bigint(20) NOT NULL AUTO_INCREMENT,
                        temp varchar(1000) NOT NULL,
                        bt varchar(1000) NOT NULL,
                        created_at datetime NOT NULL,
                        PRIMARY KEY (id)) $charset_collate;";
  dbDelta($user_shipit_table);

  $insert_user = "INSERT INTO {$wpdb->prefix}user_shipit (temp, created_at)
  VALUES('".base64_encode(str_replace(' ', '_', get_bloginfo('name'))."_shipit" . ':' . $password)."' ,NOW());";
  dbDelta($insert_user);

  $column_exists = $wpdb->get_row("
  SELECT COLUMN_NAME 
  FROM information_schema.COLUMNS 
  WHERE 
      TABLE_SCHEMA = '{$wpdb->dbname}' AND 
      TABLE_NAME = '{$wpdb->prefix}user_shipit' AND 
      COLUMN_NAME = 'bt'
  ");

  if (!$column_exists) {
    $wpdb->query("ALTER TABLE {$wpdb->prefix}user_shipit ADD bt varchar(1000) DEFAULT NULL;");
  }

  // create emergency rates table

  $emergency_rates_table = " CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shipit_emergency_rates (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    region integer NOT NULL,
    price integer NOT NULL,
    created_at datetime NOT NULL,
    PRIMARY KEY (id)) $charset_collate;";
  dbDelta($emergency_rates_table);

    // create rates request table

    $emergency_rates_table = " CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shipit_rates_request (
      id bigint(20) NOT NULL AUTO_INCREMENT,
      request longtext NOT NULL,
      PRIMARY KEY (id)) $charset_collate;";
    dbDelta($emergency_rates_table);

    //create table communes
    $charset_collate = $wpdb->get_charset_collate();
    ## CREATE COMMUNES TABLE
    $sql = "DROP TABLE IF EXISTS {$wpdb->prefix}shipit_communes;";
    $wpdb->query($sql);
  
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}shipit_communes (
      id bigint(20) NOT NULL AUTO_INCREMENT,
      commune_id integer(20) NOT NULL,
      region_id integer(20) NOT NULL,
      name varchar(10000) NOT NULL,
      created_at datetime NOT NULL,
      PRIMARY KEY (id)) $charset_collate;";
  
    dbDelta($sql);

    shipit_upgrade_subscriber_to_shop_manager($user_id);

    add_option('shipit_plugin_redirect', true);
}

register_activation_hook(__FILE__, 'activate_shipit');

function shipit_upgrade_subscriber_to_shop_manager($user_id) {
    $user = new WP_User($user_id);
    if (in_array('subscriber', $user->roles)) {
        $user->set_role('shop_manager');
    }
}

function shipit_plugin_redirect() {
  if (get_option('shipit_plugin_redirect', false)) {
      delete_option('shipit_plugin_redirect');
      wp_redirect(admin_url('admin.php?page=settings_api'));
      exit;
  }
}
add_action('admin_init', 'shipit_plugin_redirect');



?>
