<?php

    if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit();
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'pos_creds';

    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);