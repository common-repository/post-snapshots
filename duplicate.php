<?php

add_action('pre_post_update', 'pos_may_create_snapshot');

function pos_may_create_snapshot($post_id) {
    if (isset($_POST['pos_snapshot_create_new']) && intval($_POST['pos_snapshot_create_new']) === 1) {
        pos_create_snapshot($post_id);
    }
}

function pos_create_snapshot($post_id) {

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    if (wp_is_post_revision($post_id)) return;

    if (intval($post_id) < 1) return;

    $post = get_post($post_id, OBJECT);

    if ($post instanceof WP_Post) {

        if ($post->post_type === "snapshot") return;

        if (!in_array($post->post_type, get_option('pos_post_types', []))) return;

        global $wpdb;

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

        return $new_post_id;
    }
    return false;
}