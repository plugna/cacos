<?php
/*
    Plugin Name: ACOS - Custom Admin Color Scheme
    Plugin URI: https://github.com/plugna/acos
    Description: Adds a custom color scheme to the user profile section in the "Admin Color Scheme" section.
    Version: 1.0
    Author: Plugna
    Author URI: https://plugna.com/
    License: GPLv2 or later
    Text Domain: acos
*/

class ACOS_Plugin
{
    private static $colors_meta_key = 'acos_colors';
    private static $version_meta_key = 'acos_version';
    private static $nonce_action = 'acos_nonce';

    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('show_user_profile', array($this, 'add_color_scheme_field'));
        add_action('edit_user_profile', array($this, 'add_color_scheme_field'));
        //add_action('wp_enqueue_scripts', array($this, 'enqueue_dynamic_script'), 100);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dynamic_script'), 100);
        add_action('personal_options_update', array($this, 'save_color_scheme')); //on update
        add_action('edit_user_profile_update', array($this, 'save_color_scheme')); //on update

        //add_action('init', array($this, 'add_rewrite_rules'));
    }

    /**
     * Get the default colors from 'blue' color scheme
     *
     * $scheme-name: "blue";
     * $base-color: #52accc;
     * $icon-color: #e5f8ff;
     * $highlight-color: #096484;
     * $notification-color: #e1a948;
     * $button-color: #e1a948;
     *
     * $menu-submenu-text: #e2ecf1;
     * $menu-submenu-focus-text: #fff;
     * $menu-submenu-background: #4796b3;
     * @return string[]
     */
    public static function get_default_colors()
    {
        return array('#52accc', '#e5f8ff', '#096484', '#e1a948', '#e3af55', '#e2ecf1', '#4796b3');
    }

    public function enqueue_dynamic_script()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $userId = get_current_user_id();
        if (!empty($userId)) {
            $version = get_user_meta($userId, self::$version_meta_key, true);
            if (!empty($version)) {
                wp_enqueue_style('acos-dynamic', plugin_dir_url(__FILE__) . 'dynamic-css.php', array(), $userId . '-' . $version);
            }
        }
    }

    public function enqueue_scripts($hook)
    {
        if ('profile.php' !== $hook && 'user-edit.php' !== $hook) return;

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('acos', plugin_dir_url(__FILE__) . 'css/acos.css');

        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('acos', plugin_dir_url(__FILE__) . 'js/acos.js', array('jquery', 'wp-color-picker'), '', true);

        wp_localize_script('acos', 'acos_data', array(
            'security' => wp_create_nonce(self::$nonce_action),
            'plugin_url' => plugin_dir_url(__FILE__),
        ));
    }

    public function add_color_scheme_field($user)
    {
        $color_scheme = get_user_meta($user->ID, self::$colors_meta_key, true);
        $has_custom_scheme = !empty($color_scheme);

        $colors = json_decode($color_scheme, true);
        if (!is_array($colors)) {
            $colors = ['', '', '', '', '', '', ''];
        }

        ?>
        <table class="form-table" style="display:none;">
            <tr id="acos-row" class="acos-section">
                <th scope="row">Custom Admin Color Scheme</th>
                <td>
                    <label for="enable_acos" id="enable_acos_label">
                        <input
                                type="checkbox"
                                id="enable_acos"
                                name="enable_acos"
                                class="acos-toggle"
                            <?php echo $has_custom_scheme ? 'checked="checked"' : ''; ?>
                                value="true">
                        Enable
                    </label>&nbsp;
                    <?php for ($i = 1; $i <= 7; $i++) : ?>
                        <?php if (!isset($colors[$i - 1])) {
                            continue;
                        } ?>
                        <input type="text"
                               class="acos-picker"
                               id="acos_color_<?php echo esc_attr($i); ?>"
                               name="acos_color_<?php echo esc_attr($i); ?>"
                               value="<?php echo esc_attr($colors[$i - 1]); ?>"/>
                    <?php endfor; ?>
                </td>
            </tr>
        </table>
        <?php
    }


    public function save_color_scheme($user_id)
    {
        if (isset($_POST['acos_color_1'])) {
            $color_scheme = array();
            for ($i = 1; $i <= 7; $i++) {
                $color_scheme[] = sanitize_text_field($_POST['acos_color_' . $i]);
            }
            $color_scheme = json_encode($color_scheme);
        }

        if ($user_id) {

            // Increment the version number
            $css_version = (int) get_user_meta($user_id, self::$version_meta_key, true);
            update_user_meta($user_id, self::$version_meta_key, $css_version + 1);

            if (isset($_POST['enable_acos']) && $_POST['enable_acos'] == 'true') {
                update_user_meta($user_id, self::$colors_meta_key, $color_scheme);
            } else {
                delete_user_meta($user_id, self::$colors_meta_key);
            }
        }
    }

    public static function get_custom_colors()
    {
        $user_id = get_current_user_id();
        $color_scheme = get_user_meta($user_id, self::$colors_meta_key, true);

        if (!$color_scheme) {
            return [];
        }

        return json_decode($color_scheme, true);
    }

    //TODO: Use rewrite rules later
//    public function add_rewrite_rules() {
//        add_rewrite_rule('^acos.css$', __FILE__ .'dynamic-css.php', 'top');
//    }

}

new ACOS_Plugin();