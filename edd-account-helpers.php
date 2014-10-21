<?php
/**
 * Plugin Name: EDD Account Helpers
 * Plugin URI: https://devpress.com
 * Description: Functions to help display download information for logged in subscribers.
 * Version: 0.1.0
 * Author: Devin Price
 * Author URI: http://wptheming.com
 */

/**
 * Disables the #wpadmin bar for users without "edit_posts" permissions.
 */
 function edd_account_admin_bar() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		add_filter( 'show_admin_bar', '__return_false' );
	}
}
add_action( 'after_setup_theme', 'edd_account_admin_bar' );

/**
 * Redirects subscribers back to the home page if they attempt to access the dashboard.
 */
function edd_account_redirect_admin(){
	if ( ! current_user_can( 'edit_posts' ) && '/wp-admin/admin-ajax.php' != $_SERVER['PHP_SELF'] ) {
		wp_redirect( site_url() );
		exit;
	}
}
add_action( 'admin_init', 'edd_account_redirect_admin' );

/**
 * Returns license data associated with an EDD payment.
 *
 * @param object $payment
 * @return array $license_data
 */
function edd_acccount_get_licenses( $payment ) {

	// EDD Software Licensing Required
	if ( ! function_exists( 'edd_software_licensing' ) ) {
		return array();
	}

	// License data that will be returned
	$license_data = array();

	$licensing = edd_software_licensing();
	$licenses = $licensing->get_licenses_of_purchase( $payment->ID );

	if ( ! empty( $licenses ) ) :
		foreach( $licenses as $item ) :
			$key = $licensing->get_license_key( $item->ID );
			$renew_uri = edd_get_checkout_uri( array(
				'edd_license_key' => EDD_Software_Licensing::get_license_key( $item->ID ),
				'download_id'     => EDD_Software_Licensing::get_download_id( $item->ID )
			) );
			$expiration = $licensing->get_license_expiration( $item->ID );
			$expiration = esc_html( date_i18n( get_option( 'date_format' ), $expiration ) );
			$license_data[$key] = array(
				'key' => $key,
				'status' => $licensing->get_license_status( $item->ID ),
				'renew_uri' => $renew_uri,
				'expiration' => $expiration
			);
		endforeach;
	endif;

	return $license_data;
}

/**
 * Returns an array of download data for the user.
 *
 * @param string user_id
 * @param string thumbnail size
 */
function edd_account_get_user_downloads( $user, $size = 'large' ) {

	// Retrieve up to 100 purchases for the current user
	// Watch out, no pagination
	$purchases = edd_get_users_purchases( $user, 100, true, 'any' );

	// Download data to be returned
	$download_data = array();

	if ( $purchases ) : foreach ( $purchases as $payment ) :

		$downloads = edd_get_payment_meta_cart_details( $payment->ID, true );
		$purchase_data = edd_get_payment_meta( $payment->ID );
		$email = edd_get_payment_user_email( $payment->ID );
		$licenses = edd_acccount_get_licenses( $payment );

		if ( $downloads && edd_is_payment_complete( $payment->ID ) ) :

			foreach ( $downloads as $download ) :
				$price_id = edd_get_cart_item_price_id( $download );
				$name = get_the_title( $download['id'] );
				$version = get_post_meta( $download['id'], '_edd_sl_version', true );
				$download_files = edd_get_download_files( $download['id'], $price_id );
				if ( $download_files ) : foreach ( $download_files as $filekey => $file ) :
					$download_url = edd_get_download_file_url( $purchase_data['key'], $email, $filekey, $download['id'], $price_id );
					$download_data[] = array(
						'name' => esc_html( $file['name'] ),
						'image' => get_the_post_thumbnail( $download['id'], $size ),
						'download_url' => esc_url( $download_url ),
						'version' => $version,
						'licenses' => $licenses
					);
				endforeach; endif;
			endforeach;

		endif;

	endforeach; endif;

	return $download_data;
}