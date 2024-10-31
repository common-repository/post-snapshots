<?php
/*
 * This legacy files contains the functionality to create snapshots via the WordPress posts overview page.
 */
function pos_duplicate_post_as_snapshot() {
    global $wpdb;
    if (!(isset($_GET['post']) || isset($_POST['post']) || (isset($_REQUEST['action']) && 'pos_duplicate_post_as_snapshot' == $_REQUEST['action']))) {
        wp_die('No post to duplicate has been supplied!');
    }

    /*
     * Nonce verification
     */
    if (!isset($_GET['duplicate_nonce']) || !wp_verify_nonce($_GET['duplicate_nonce'], basename(__FILE__)))
        return;

    /*
     * get the original post id
     */
    $post_id = (isset($_GET['post']) ? absint($_GET['post']) : absint($_POST['post']));

    /*
     * and all the original post data then
     */
    $post = get_post($post_id);

    /*
     * if post data exists, create the post duplicate
     */
    if (isset($post) && $post != null) {

        /*
         * new post data array
         */
        $args = array(
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'post_author' => $post->post_author,
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_name' => 'snap-' . $post->post_name,
            'post_parent' => $post->post_parent,
            'post_password' => $post->post_password,
            'post_status' => 'snapshot',
            'post_title' => $post->post_title,
            'post_type' => $post->post_type,
            'to_ping' => '',
            'menu_order' => $post->menu_order
        );

        /*
         * insert the post by wp_insert_post() function
         */
        $new_post_id = wp_insert_post($args);

        /*
         * get all current post terms ad set them to the new post draft
         */
        $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
        }

        /*
         * duplicate all post meta just in two SQL queries
         */
        $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
        if (count($post_meta_infos) != 0) {
            $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
            foreach ($post_meta_infos as $meta_info) {
                $meta_key = $meta_info->meta_key;
                if ($meta_key == '_wp_old_slug') continue;
                $meta_value = addslashes($meta_info->meta_value);
                $sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
            }
            $sql_query .= implode(" UNION ALL ", $sql_query_sel);
            $wpdb->query($sql_query);
        }

        /*
         * Add snapshot information
         */
        $current_user = wp_get_current_user();
        $snapshot_author = $current_user->ID;


        update_post_meta($new_post_id, 'snapshot_origin', $post_id);
        update_post_meta($new_post_id, 'snapshot_time', time());
        update_post_meta($new_post_id, 'snapshot_author', $snapshot_author);


        $conv_title = apply_filters('the_title', $post->post_title);
        $conv_content = apply_filters('the_content', $post->post_content);
        $date = date("Y-m-d H:i:s", time());

        $html = <<<SNAPHTML
            <div class="snapshot snapshot-{$new_post_id} snapshot-for-{$post_id}">
                <h1 class="title">{$conv_title}</h1>
                <div class="content">{$conv_content}</div>
                <div class="meta">
                    <div>Created: {$date}</div>
                    <div>Author: {$current_user->user_email}</div>
                </div>
            </div>
SNAPHTML;


        update_post_meta($new_post_id, 'snapshot_html', $html);


        /*
         * finally, redirect to the edit post screen for the new draft
         */
        wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
        exit;
    } else {
        wp_die('Post creation failed, could not find original post: ' . $post_id);
    }
}

add_action('admin_action_pos_duplicate_post_as_snapshot', 'pos_duplicate_post_as_snapshot');

/*
 * Add the duplicate link to action list for post_row_actions
 */
function pos_duplicate_post_link($actions, $post) {
    if (current_user_can('edit_posts')) {
        $actions['snapshot'] = '<a href="' . wp_nonce_url('admin.php?action=pos_duplicate_post_as_snapshot&post=' . $post->ID, basename(__FILE__), 'duplicate_nonce') . '" title="Snapshot this item" rel="permalink">New Snapshot</a>';
    }
    return $actions;
}

function pos_create_snapshot_filters() {

    foreach (get_option('pos_post_types', []) as $post_type) {
        add_filter($post_type . '_row_actions', 'pos_duplicate_post_link', 10, 2);
    }
}

add_action('init', 'pos_create_snapshot_filters');