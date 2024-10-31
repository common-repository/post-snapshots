<?php
// only called if the plugin is deleted
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// delete all snapshots and clean the database
global $wpdb;
$wpdb->query("DELETE * FROM $wpdb->posts WHERE post_status='snapshot'");