<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * We are not using an uninstall hook because WordPress perfoms bad when using it.
 * Even if below issue is "fixed", it did not resolve the perfomance issue.
 *
 * @see https://core.trac.wordpress.org/ticket/31792
 *
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - Check if the $_REQUEST['plugin'] content actually is woocommerce-birthday-field/woocommerce-birthday-field.php
 * - Check if the $_REQUEST['action'] content actually is delete-plugin
 * - Run a check_ajax_referer check to make sure it goes through authentication
 * - Run a current_user_can check to make sure current user can delete a plugin
 *
 *
 * @link       https://github.com/bsh
 * @since      1.0.0
 * @package    Woocommerce_Birthday_Field
 */

/**
 * Perform Uninstall Actions.
 *
 * If uninstall not called from WordPress,
 * If no uninstall action,
 * If not this plugin,
 * If no caps,
 * then exit.
 *
 * @since 1.0.0
 */
function woo_pi_uninstall() {

	if ( ! defined( 'WP_UNINSTALL_PLUGIN' )
		|| empty( $_REQUEST )
		|| ! isset( $_REQUEST['plugin'] )
		|| ! isset( $_REQUEST['action'] )
		|| 'woocommerce-birthday-field/woocommerce-birthday-field.php' !== $_REQUEST['plugin']
		|| 'delete-plugin' !== $_REQUEST['action']
		|| ! check_ajax_referer( 'updates', '_ajax_nonce' )
		|| ! current_user_can( 'activate_plugins' )
	) {
        exit;
    }
    /**
     * It is now safe to perform your uninstall actions here.
     *
     * @see https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/#method-2-uninstall-php
     */
}

woo_pi_uninstall();
