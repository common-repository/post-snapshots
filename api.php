<?php

/**
 * Get the latest snapshot for a given post.
 *
 * @param $post_id
 * @return WP_Post|null
 */
function pos_get_latest_snapshot($post_id) {
    $q = new WP_Query([
        'post_type' => 'any',
        'post_status' => 'snapshot',
        'posts_per_page' => 1,
        'meta_key' => 'snapshot_origin',
        'meta_value' => $post_id,
        'orderby' => 'post_date',
    ]);

    if ($q->have_posts()):
        return $q->posts[0];
    else:
        return null;
    endif;
}

/**
 * Create a snapshot (same as pos_create_snapshot).
 *
 * @param $post_id
 * @return The snapshot ID or false.
 */
function pos_create_snapshot_from($post_id) {
    return pos_create_snapshot($post_id);
}

/**
 * Completely delete a snapshot by its ID.
 *
 * @param $snap_id
 */
function pos_delete_snapshot($snap_id) {
    wp_delete_post($snap_id, true);
}

/**
 * @param $snap_id : Snapshot ID.
 * @return string: The URL to the plainview.
 */
function pos_get_plainview_url($snap_id) {
    return get_bloginfo('url') . '?pos_view_plain=' . intval($snap_id);
}