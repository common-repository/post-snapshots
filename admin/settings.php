<?php
// Create settings page (especially for setting the post types)

/********** Tooltips settings page *********/
add_action('admin_menu', 'pos_settings');

function pos_settings() {
    add_submenu_page(
        "options-general.php",
        __('Post Snapshots', "pos"),
        'Post Snapshots',
        'manage_options',
        "post-snapshots",
        'pos_show_admin_page'
    );
}

function pos_show_admin_page() { ?>
    <div class="wrap">
        <h2><?php _e('Post Snapshot: Settings Page', POS_TD); ?></h2>
        <p>&nbsp;</p>

        <form method="post" action="">
            <table class="widefat">
                <thead>
                <tr valign="top">
                    <th scope="row"><?php _e('Setting', POS_TD); ?></th>
                    <th scope="row"><?php _e('Value', POS_TD); ?></th>
                    <th scope="row"><?php _e('Description', POS_TD); ?></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td><?php _e('Enable for Post Type', POS_TD); ?></td>
                    <td>
                        <?php
                        $ptos = get_post_types('', 'objects');
                        $ptoprep = [];
                        foreach ($ptos as $p) {
                            $ptoprep[$p->name] = $p->label;
                        }

                        echo get_html_selector_for_array($ptoprep, 'pos_post_types', false, true, false, "", get_option('pos_post_types'), true); ?>
                    </td>
                    <td class="description"><?php _e('Choose all post types you want to enable post snapshots for.', POS_TD); ?></td>
                </tr>
                </tbody>
            </table>

            <input type="hidden" name="update_pos_settings" value="do"/>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'pos_update_settings'); // for registering the settings group

function pos_update_settings($input) {

    if (!is_admin()) {
        return;
    }

    if (!current_user_can('manage_options') && (!wp_doing_ajax())) {
        return;
    }

    if (isset($_POST['update_pos_settings']) && $_POST['update_pos_settings'] == 'do') {
        update_option('pos_post_types', $_POST['pos_post_types']);

        $error = false;

        if ($error) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error is-dismissible">
                    <p>' . __('One or more callback functions have not been saved due to invalid characters.', POS_TD) . '</p>
                </div>';
            });
        } else {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible">
                    <p>' . __('The plugin settings have been updated successfully.', POS_TD) . '</p>
                </div>';
            });
        }
    }
}