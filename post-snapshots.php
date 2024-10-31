<?php
/**
 * Plugin Name: Post Snapshots
 * Version: 0.9
 * Plugin URI: https://ready2order.com/en/contact/
 * Description: Create, manage and view snapshots of any post (or custom post type) whenever you want. Like user-managed revisions with a lot of useful functionality.
 * Author: David Stöckl
 * Text Domain: post-snapshots
 * Domain Path: /languages/
 * License: GPL
 */

// prevent strange fatal error, but we do not have network admin functionality anyways
if (is_network_admin()) {
    return;
}

define('POS_PLUGIN_ACTIVE', true);
define('POS_TD', 'post-snapshots');

require('duplicate.php');
require('admin/admin.php');
require('api.php');

// register the custom post status "snapshot"
function pos_register_post_status() {

    $public = false;

    if (!is_admin()) {
        if (is_singular()) {
            global $wp_query;
            $path = get_query_var('name');
            if (!$path) {
                $path = get_query_var('pagename');
            }
            $prefetch = get_page_by_path($path, OBJECT, get_post_types());

            if ($prefetch && $prefetch->post_status === "snapshot") {
                $public = true;
            }
        }
    }

    register_post_status('snapshot', array(
        'label' => _x('Post Snapshot', POS_TD),
        'public' => $public,
        'exclude_from_search' => true,
        'show_in_admin_all_list' => false,
        'show_in_admin_status_list' => false,
        'label_count' => _n_noop('Post Snapshot <span class="count">(%s)</span>', 'Post Snapshots <span class="count">(%s)</span>'),
    ));
}

add_action('pre_get_posts', 'pos_register_post_status');

function pos_register_post_status_admin() {
    if (is_admin()) {
        register_post_status('snapshot', array(
            'label' => _x('Post Snapshot', POS_TD),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => false,
            'show_in_admin_status_list' => false,
            'label_count' => _n_noop('Post Snapshot <span class="count">(%s)</span>', 'Post Snapshots <span class="count">(%s)</span>'),
        ));
    }
}

add_action('init', 'pos_register_post_status_admin');


// just some admin styles for the meta box and the edit page
function pos_admin_scripts() {
    wp_register_style('post-snapshots-admin-style', plugin_dir_url(__FILE__) . 'admin/admin.css', false, '1.0.0');
    wp_enqueue_style('post-snapshots-admin-style');
}

add_action('admin_enqueue_scripts', 'pos_admin_scripts');


// disable snapshot editing in wp-admin
function pos_restrict_editing_snapshots() {
    if (is_admin() && isset($_GET["post"])) {
        $p = $_GET['post'];

        $post = get_post($p);

        if ($post->post_status === "snapshot") {
            wp_die("Editing snapshots is strictly forbidden. You can delete them using the snapshot meta box instead.");
        }
    }
}

add_action('admin_init', 'pos_restrict_editing_snapshots');

// add the snapshot creation functionality to wp-admin
function pos_add_snapshots_to_publish_box($post_obj) {

    if (in_array($post_obj->post_type, get_option('pos_post_types', []))) { ?>
        <div class="misc-pub-section misc-pub-section-last">
            <label>
                <input type="checkbox" <?php (!empty($value) ? ' checked="checked" ' : null); ?> value="1"
                       name="pos_snapshot_create_new"/>
                <?php _e('Create Snapshot', POS_TD); ?>
            </label>
        </div>
    <?php }
}

add_action('post_submitbox_misc_actions', 'pos_add_snapshots_to_publish_box');

// force delete all snapshots if the main post is deleted
function pos_delete_snapshots($post_id) {
    $snapshots = new WP_Query([
        'post_status' => 'snapshot',
        'posts_per_page' => -1,
        'meta_key' => 'snapshot_origin',
        'meta_value' => $post_id,
        'orderby' => 'post_date'
    ]);

    if ($snapshots->have_posts()):
        while ($snapshots->have_posts()): $snapshots->the_post();
            wp_delete_post(get_the_ID(), true);
        endwhile;
    endif;
}

add_action('delete_post', 'pos_delete_snapshots');

function pos_no_index_snapshots() {
    if (is_single()) {
        global $post;
        if ($post->post_type === "snapshot") {
            wp_no_robots();
        }
    }
}

add_action('wp_head', 'pos_no_index_snapshots');


function pos_check_view_plain() {
    if (isset($_GET["pos_view_plain"]) && intval($_GET["pos_view_plain"]) > 0 && current_user_can('edit_posts')) {

        $pid = intval($_GET["pos_view_plain"]);

        ?><!DOCTYPE html>
        <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo __('Post Snapshot Plaintext View:', POS_TD) . ' ' . get_the_title($pid); ?></title>
        <style type="text/css">
            #pos_plaintextnav {
                padding: 10px;
                text-align: center;
                background-color: #333;
                border-bottom: 1px solid #11e;
                font-family: "Verdana", sans-serif;
            }

            #pos_plaintextnav ul li {
                display: inline-block;
                padding: 0 10px;
            }

            #pos_plaintextnav ul li a {
                color: #e0e0e0;
                transition: color .2s;
            }

            #pos_plaintextnav ul li a:hover, #pos_plaintextnav ul li a:focus {
                color: #fff;
            }

            @media print {
                #pos_plaintextnav {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
    <div id="pos_plaintextnav">
        <nav>
            <ul>
                <li>
                    <a href="#" onclick="window.print();">Print (e.g. as PDF)</a>
                </li>
                <li>
                    <a href="<?php the_permalink($pid); ?>" target="_blank"><?php _e('View on site', POS_TD); ?></a>
                </li>
                <li>
                    <a href="<?php echo get_the_permalink(intval(get_post_meta($pid, 'snapshot_origin', true))); ?>"
                       target="_blank">
                        <?php _e('View current version', POS_TD); ?>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php echo get_post_meta($pid, 'snapshot_html', true); ?>
    </body>
        </html><?php
        die;
    }
}

add_action('template_redirect', 'pos_check_view_plain');