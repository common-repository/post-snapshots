<?php
// Create post meta box (in order to list all post snapshots)
add_action('add_meta_boxes', 'pos_add_snapshot_list_box');

function pos_add_snapshot_list_box() {
    $screens = get_option('pos_post_types', []);

    foreach ($screens as $screen) {
        add_meta_box(
            'pos_snapshot_box', // Unique ID
            'Post Snapshots', // Box title
            'pos_post_meta_box_html', // Content callback, must be of type callable
            $screen // Post type
        );
    }
}

function pos_post_meta_box_html($post) { ?>
    <div class="pos_post_meta_box">
        <?php

        $q = new WP_Query([
            'post_type' => $post->post_type,
            'post_status' => 'snapshot',
            'posts_per_page' => -1,
            'meta_key' => 'snapshot_origin',
            'meta_value' => $post->ID,
            'orderby' => 'post_date'
        ]);

        // this should not be necessary, but the wp query parameters make problems for some reason and do not apply the order
        usort($q->posts, function ($a, $b) {
            if ($a->post_date > $b->post_date) {
                return -1;
            } else if ($a->post_date < $b->post_date) {
                return 1;
            }
            return 0;
        });

        if ($q->have_posts()):
            ?>
            <div class="pos_post_meta_box_available">
                <p><?php _e('The following post snapshots exist for this post:', POS_TD); ?></p>
                <ul class="pos_post_meta_box_list">
                    <?php

                    while ($q->have_posts()): $q->the_post();
                        ?>
                        <li class="pos_snapshot">
                        <span class="pos_snapshot_date"><?php echo get_the_date('Y-m-d', get_the_ID()) . ' ' . __('at', POS_TD)
                                . ' ' . get_the_date('H:i:s', get_the_ID()); ?></span>
                            <span class="seperator">-</span>
                            <span class="pos_snapshot_id"><?php echo get_the_ID(); ?></span>
                            <span class="seperator">-</span>
                            <span class="pos_snapshot_name">
                            <input type="text" name="pos-snapshot-name-<?php echo get_the_ID(); ?>"
                                   value="<?php echo get_post_meta(get_the_ID(), 'snapshot_name', true); ?>"/>
                        </span>
                            <span class="seperator">-</span>
                            <span class="pos_snapshot_plain pos-pm-action">
                            <a target="_blank" href="<?php echo pos_get_plainview_url(get_the_ID()); ?>"
                               title="Plain Snapshot">
                                <span class="dashicons dashicons-media-code"></span>
                            </a>
                        </span>
                            <span class="seperator">-</span>
                            <span class="pos_snapshot_view pos-pm-action">
                            <a target="_blank" href="<?php the_permalink(); ?>" title="View this snapshot">
                                <span class="dashicons dashicons-welcome-view-site"></span>
                            </a>
                        </span>
                            <span class="seperator">-</span>
                            <span class="pos_snapshot_delete">
                            <label><input type="checkbox" name="pos-snapshot-dele-<?php echo get_the_ID(); ?>"
                                          value="1"/> <?php _e('Delete', POS_TD); ?></label>
                        </span>
                        </li>
                    <?php
                    endwhile;
                    ?>
                </ul>
                <input type="hidden" name="pos-update-snapshots" value="do"/>
            </div>
        <?php
        else:
            ?>
            <p><?php _e('Currently there are no snapshots available for this post. Create your first snapshot by checking the snapshot meta box near the publish post options.', POS_TD); ?></p>
        <?php
        endif;
        wp_reset_postdata();
        ?>
    </div>
    <?php
}

function pos_update_snapshot_properties($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_status === "snapshot") return;

    if (isset($_POST["pos-update-snapshots"]) && $_POST["pos-update-snapshots"] === "do") {

        // update the names
        $names = array_filter(
            $_POST,
            function ($key) {
                return (substr($key, 0, 18) === "pos-snapshot-name-");
            },
            ARRAY_FILTER_USE_KEY
        );

        foreach ($names as $key => $value) {
            update_post_meta(intval(substr($key, 18)), "snapshot_name", $value);
        }

        // delete the snapshots to be deleted
        $todelete = array_filter(
            $_POST,
            function ($key) {
                return (substr($key, 0, 18) === "pos-snapshot-dele-");
            },
            ARRAY_FILTER_USE_KEY
        );

        foreach ($todelete as $key => $value) {
            if (intval($value) > 0) {
                wp_delete_post(intval(substr($key, 18)), true);
            }
        }
    }
}

add_action('save_post', 'pos_update_snapshot_properties', 10, 3);