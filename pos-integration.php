<?php
/**
 * Plugin Name: POS Integration
 * Plugin URI: https://logico.co/
 * Description: An e-commerce plugin for SquareUp & LightSpeedQ Products integration.
 * Version: 1.0.0
 * Author: Logico Global
 * Author URI: https://logico.co/
 * WC requires at least: 3.0
 * WC tested up to: 6.0.0
 * License: GPL2
 * TextDomain: pop-pos
 */


defined('ABSPATH') or die('Hey, you can\t access this file, you silly human');


// add_filter( 'dokan_get_dashboard_nav', 'add_review_menussss' );


// function add_review_menussss( $urls ) {

//     $urls['pos'] = array(
//         'title'      => __( 'Connect POS', 'dokan' ),
//         'icon'       => '<i class="fa fa-life-ring"></i>',
//         'url'        => 'https://www.google.com',
//         'pos'        => 10,
//         //'permission' => 'dokan_view_review_menu'
//     );

//     return $urls;
// }


add_filter( 'dokan_query_var_filter', 'dokan_load_document_menu' );

function dokan_load_document_menu( $query_vars ) {
    $query_vars['pos'] = 'pos';
    return $query_vars;
}

add_filter( 'dokan_get_dashboard_nav', 'dokan_add_help_menu' );
function dokan_add_help_menu( $urls ) {
    $urls['pos'] = array(
        'title' => __( 'Connect POS', 'dokan'),
        'icon'  => '<i class="fa fa-life-ring"></i>',
        'url'   => dokan_get_navigation_url( 'pos' ),
        'pos'   => 10
    );
    return $urls;
}

add_action('wp_enqueue_scripts', 'enqueue');

function enqueue()
    {
        // equeue all our scripts
        wp_enqueue_style('font-awesome-pos', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.0/css/all.min.css');

        wp_enqueue_style('mypluginstyle', plugin_dir_url(__FILE__) . 'assets/css/pos_style.css');
        wp_enqueue_script('bt-script', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js');

        wp_enqueue_script('pos-script', plugin_dir_url(__FILE__) . 'assets/js/pos_script.js', array( 'jquery' ), '1.0.0', true);
    }

add_action( 'dokan_load_custom_template', 'dokan_load_template' );
function dokan_load_template( $query_vars ) {
    if ( isset( $query_vars['pos'] ) ) {
        require_once dirname( __FILE__ ). '/help.php';
       }
}


register_activation_hook( __FILE__, 'activate_pos' );

function activate_pos(){

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $tablename = $wpdb->prefix . 'pos_creds';
    $tablename1 = $wpdb->prefix . 'square_creds';

    $sql =  "CREATE TABLE IF NOT EXISTS $tablename(
        id int(11) NOT NULL AUTO_INCREMENT,
        access_token varchar(255) NOT NULL,
        app_key varchar(255) NOT NULL,
        secret_key varchar(255) NOT NULL,
        user_id varchar(255) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY(user_id) 
    )";

    $sql1 =  "CREATE TABLE IF NOT EXISTS $tablename1(
        id int(11) NOT NULL AUTO_INCREMENT,
        access_token varchar(255) NOT NULL,
        expires_at varchar(255) NOT NULL,
        merchant_id varchar(255) NOT NULL,
        refresh_token varchar(255) NOT NULL,
        user_id varchar(255) NOT NULL,
        status int(11) NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY(user_id) 
    )";


    require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

    dbDelta( $sql );

    dbDelta( $sql1 );
}

require_once( __DIR__ . '/functions.php' );