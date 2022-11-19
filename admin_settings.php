<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/bsh
 * @since      1.0.0
 *
 * @package    WooCommerce_Birthday_Field
 * @subpackage WooCommerce_Birthday_Field/admin/partials
 */
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <br/>
    <form method="post" action="options.php">
        <?php
        $options = get_option($this->plugin_name);
        settings_fields($this->plugin_name);
        do_settings_sections($this->plugin_name);
        if (! class_exists('MC4WP_MailChimp')) {
            echo "<div class=\"notice\">".__('Warning', $this->plugin_name).": <a href=\"https://wordpress.org/plugins/mailchimp-for-wp/\" target=\"_blank\">MC4WP</a> ".__('not activated', $this->plugin_name).".</div>";
        }
        ?>

        <h2 class="title"><?php esc_html_e('MC4WP field settings', $this->plugin_name); ?></h2>

        <p class="description">
            <?php esc_html_e("Copy tag from Audience > All Contacts > Settings > Audience fields and *|MERGE|* tags. If you don't have birthday tag, add it in the form builder.", $this->plugin_name); ?>
        </p>

        <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row"><label for="<?php echo $this->plugin_name; ?>-field_tag">Mailchimp Field Tag</label></th>
                <td>
                    <input name="<?php echo $this->plugin_name; ?>[field_tag]" type="text" id="<?php echo $this->plugin_name; ?>-field_tag" value="<?php echo $options['field_tag'] ?? ''; ?>" class="regular-text">
                </td>
            </tr>
            </tbody>
        </table>

        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e('Save settings', $this->plugin_name); ?>"></p>
    </form>
</div>