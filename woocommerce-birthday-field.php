<?php
/**
 * @link              https://github.com/bsh
 * @package           WooCommerce_Birthday_Field
 *
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Birthday Field
 * Plugin URI:        #
 * Description:       WooCommerce Birthday Field
 * Version:           1.0.0
 * Author:            Laszlo Kovacs
 * Requires PHP:      7.4
 * Tested up to (WP): 6.1.1
 * Tested up to (WC): 7.1.0
 * Author URI:        https://github.com/bsh/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-birthday-field
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

/**
 * Define the Plugin basename
 */
define('WOOCOMMERCE_BIRTHDAY_FIELD_BASE_NAME', plugin_basename(__FILE__));

if (! class_exists('WoocommerceBirthdayFiled')) {
    /**
     * Main plugin class
     */
    class WoocommerceBirthdayFiled
    {
        protected $plugin_name;

        /**
         * Setup plugin on initializing class object
         */
        public function __construct()
        {
            $this->plugin_name = 'woocommerce-birthday-field';

            $this->setup_actions();
        }

        /**
         * Setup Hooks
         */
        public function setup_actions()
        {
            if (is_admin()) {
                // Check woo
                add_action('admin_init', [$this, 'check_required_plugin']);

                // Languages
                add_action('plugins_loaded', [$this, 'load_textdomain']);

                // Settings menu
                add_action('admin_init', [$this, 'options_update']);
                add_action('admin_menu', [$this, 'add_plugin_admin_menu']);

                // Settings button
                add_filter('plugin_action_links_'.plugin_basename(__FILE__), [$this, 'add_action_links']);
            }
            // Birthday Field
            add_action('woocommerce_edit_account_form', [$this, 'action_woocommerce_edit_account_form']);
            add_action('woocommerce_save_account_details', [$this, 'action_woocommerce_save_account_details'], 10, 1);
            add_action('show_user_profile', [$this, 'add_user_birthday_field'], 10, 1);
            add_action('edit_user_profile', [$this, 'add_user_birthday_field'], 10, 1);
            add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'birth_day_checkout_field_display_admin_order_meta'], 10, 1);
            add_action('personal_options_update', [$this, 'save_user_birthday_field'], 10, 1);
            add_action('edit_user_profile_update', [$this, 'save_user_birthday_field'], 10, 1);
            add_action('woocommerce_save_account_details', [$this, 'save_user_birthday_field'], 10, 1);
            add_action('woocommerce_after_order_notes', [$this, 'birth_day_checkout_field']);
            add_action('woocommerce_checkout_update_order_meta', [$this, 'birth_day_checkout_field_update_order_meta']);

            /* Mailchimp sync */
            if (class_exists('MC4WaP_MailChimp')) {
                $settings = WoocommerceBirthdayFiled::get_settings();
                if ($settings and isset($settings['field_tag']) and $settings['field_tag'] != '') {
                    add_filter('mc4wp_user_sync_subscriber_data', [
                        $this,
                        'process_mc4wp_user_sync_subscriber_data',
                    ], 10, 2);
                }
            }
        }

        /**
         * Add field - my account
         *
         * @return void
         */
        public function action_woocommerce_edit_account_form()
        {
            woocommerce_form_field('birthday_field', [
                'type'              => 'date',
                'priority'          => 2,
                'label'             => __('Birthday', $this->plugin_name),
                'placeholder'       => __('Birthday', $this->plugin_name),
                'class'             => 'woocommerce-form-row woocommerce-form-row--wide',
                'required'          => false,
                'custom_attributes' => [
                    'min' => date('Y-m-d', strtotime("-130 year", time())),
                    'max' => date('Y-m-d'),

                ],
            ], get_user_meta(get_current_user_id(), 'birthday_field', true));
        }

        /**
         * Validate - my account
         *
         * @param $args
         * @return void
         */
        /*function action_woocommerce_save_account_details_errors($args)
        {
            if (isset($_POST['birthday_field']) && empty($_POST['birthday_field'])) {
                $args->add('error', __('Kérlek dátumot adj meg', 'woocommerce'));
            }
        }*/

        /**
         * Save - my account
         *
         * @param $user_id
         * @return void
         */
        public function action_woocommerce_save_account_details($user_id)
        {
            if (isset($_POST['birthday_field']) && ! empty($_POST['birthday_field'])) {
                update_user_meta($user_id, 'birthday_field', sanitize_text_field($_POST['birthday_field']));
            }
        }

        /**
         * Add field - admin
         *
         * @param $user
         * @return void
         */
        public function add_user_birthday_field($user)
        {
            ?>
            <h3><?php _e('Birthday', $this->plugin_name); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="birthday_field"><?php _e('Birthday', $this->plugin_name); ?></label></th>
                    <td><input type="date" min="<?php echo date('Y-m-d', strtotime("-130 year", time())); ?>" max="<?php echo date('Y-m-d'); ?>" name="birthday_field" value="<?php echo esc_attr(get_the_author_meta('birthday_field', $user->ID)); ?>" class="regular-text"/></td>
                </tr>
            </table>
            <br/>
            <?php
        }

        /**
         * Display field value on the order edit page
         *
         * @param $order
         * @return void
         */
        public function birth_day_checkout_field_display_admin_order_meta($order)
        {
            echo '<p><strong>'.__('Birthday', $this->plugin_name).':</strong> '.get_post_meta($order->id, 'birthday_field', true).'</p>';
        }

        /**
         * Save field - admin
         *
         * @param $user_id
         * @return void
         */
        function save_user_birthday_field($user_id)
        {
            if (! empty($_POST['birthday_field'])) {
                update_user_meta($user_id, 'birthday_field', sanitize_text_field($_POST['birthday_field']));
            }
        }

        /**
         * Add the field to the checkout
         *
         * @param $checkout
         * @return void
         */
        public function birth_day_checkout_field($checkout)
        {
            if (! empty(get_user_meta(get_current_user_id(), 'birthday_field', true))) {
                $checkout_birth_date = esc_attr(get_user_meta(get_current_user_id(), 'birthday_field', true));
            } else {
                $checkout_birth_date = $checkout->get_value('birth_date');
            }

            echo '<div id="birthday_field">';

            woocommerce_form_field('birthday_field', [
                'type'              => 'date',
                'class'             => ['birth-date form-row-wide'],
                'label'             => __('Birthday', $this->plugin_name),
                'placeholder'       => __('Birthday', $this->plugin_name),
                'required'          => false,
                'custom_attributes' => [
                    'min' => date('Y-m-d', strtotime("-130 year", time())),
                    'max' => date('Y-m-d'),

                ],
            ], $checkout_birth_date);

            echo '</div>';
        }

        /**
         * Update order meta with field value
         *
         * @param $order_id
         * @return void
         */
        public function birth_day_checkout_field_update_order_meta($order_id)
        {
            if (! empty($_POST['birthday_field'])) {
                update_post_meta($order_id, 'birthday_field', sanitize_text_field($_POST['birthday_field']));
                update_user_meta(get_post_meta($order_id, '_customer_user', true), 'birthday_field', sanitize_text_field($_POST['birthday_field']));
            }
        }

        /**
         * Load plugin text domain for translation purpose
         *
         * @return void
         */
        public function load_textdomain()
        {
            load_plugin_textdomain($this->plugin_name, false, dirname(plugin_basename(__FILE__)).'/languages/');
        }

        /**
         * Check if required plugin is available
         */
        public function check_required_plugin()
        {
            if (is_admin() && current_user_can('activate_plugins') && ! class_exists('WooCommerce')) {
                add_action('admin_notices', [$this, 'plugin_notice']);
                deactivate_plugins(WOOCOMMERCE_BIRTHDAY_FIELD_BASE_NAME);

                if (isset($_GET['activate'])) { // phpcs:ignore WordPress.Security.NonceVerification
                    unset($_GET['activate']); // phpcs:ignore WordPress.Security.NonceVerification
                }
            }
        }

        /**
         * A settings link to plugins page
         */
        public function add_action_links($links): array
        {
            $settings_link = [
                '<a href = "'.admin_url('options-general.php?page='.$this->plugin_name).'" > '.__('Settings', $this->plugin_name).' </a > ',
            ];

            return array_merge($settings_link, $links);
        }

        /**
         * Settings page
         *
         * @return void
         */
        public function display_plugin_setup_page()
        {
            if ($this->has_required_permissions()) {
                include_once('admin_settings.php');
            }
        }

        /**
         * @return void
         */
        public function options_update()
        {
            if ($this->has_required_permissions()) {
                register_setting($this->plugin_name, $this->plugin_name, [$this, 'validate']);
            }
        }

        /**
         * @param $input
         * @return array|false
         */
        public function validate($input)
        {
            $valid = [];

            $valid['field_tag'] = sanitize_text_field($input['field_tag']);

            return $valid;
        }

        /**
         * @return void
         */
        public function add_plugin_admin_menu()
        {
            add_options_page('WooCommerce Birthday Field', 'WooCommerce Birthday Field', 'manage_options', $this->plugin_name, [$this, 'display_plugin_setup_page']);
        }

        /**
         * @return mixed
         */
        public static function get_settings()
        {
            global $wpdb;

            return unserialize($wpdb->get_var($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s", 'woocommerce-birthday-field')));
        }

        /**
         * Check if user have permissions
         */
        public function has_required_permissions(): bool
        {
            if (is_user_logged_in()) {
                $user = wp_get_current_user();
                $role = (array) $user->roles;
                if (in_array('administrator', $role, true) || in_array('shop_manager', $role, true) || current_user_can('manage_woocommerce')) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        /**
         * Show plugin activation notice
         */
        public function show_notice()
        {
            ?>
            <div class="error"><p> <?php esc_html_e('Please activate WooCommerce plugin before using', $this->plugin_name); ?> <strong><?php esc_html_e('WooCommerce Birthday Field', $this->plugin_name); ?></strong> <?php esc_html_e('plugin', $this->plugin_name); ?>.</p></div>
            <?php
        }

        public function process_mc4wp_user_sync_subscriber_data($subscriber, $user)
        {
            $settings = WoocommerceBirthdayFiled::get_settings();
            $subscriber->merge_fields[(string) $settings['field_tag']] = date('m/d', strtotime($user->birthday_field));

            return $subscriber;
        }
    }

    (new WoocommerceBirthdayFiled());
}
