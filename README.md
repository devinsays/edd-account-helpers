# EDD Account Helpers

Easy Digital Downloads is a great tools for selling digital products through your website, but building a nice dashboard area for user accounts still takes a good time investment. This plugin isn't meant to be used as-is, but I've built a couple functions that might make this a bit easier.

None of this code has been extensively tested. If you see improvements or have questions, feel free to open an issue.

## View all Downloads

The main goal of this plugin is to return download information for logged in users.

This is some of the data I choose to display:

* Download Name
* Download Featured Image
* Download Link

If licensing is enabled (EDD Software Licensing Add-On), I also wanted to show:

* Download License Key
* License Key Expiration Date
* License Key Renewal Link

You may want to show additional information, such as the changelog. That should also be easier to return if you hack the code a bit.

To get a list of downloads for the a user:

```
/**
 * Returns an array of download data for the user.
 *
 * @param string user_id
 * @param string thumbnail size
 */
$downloads = edd_account_get_user_downloads( $user_id, $size );
```

Here's an example dashboard page that outputs some of this data:

```
<?php if ( function_exists('edd_get_users_purchases') && function_exists('edd_account_get_user_downloads') ) :

	// Set user as logged in user
	global $current_user;
	get_currentuserinfo();
	$downloads = edd_account_get_user_downloads( $current_user->ID, 'medium' );
	if ( $downloads ) : foreach ( $downloads as $download ) :
		echo '<p>' . $download['name'] . '</p>';
		echo '<p>' . $download['image'] . '</p>';
		echo '<p>' . $download['download_url'] . '</p>';
		echo '<p>' . $download['version'] . '</p>';
		foreach ( $download['licenses'] as $license ) :
			echo '<p>' . $license['key'] . '</p>';
			echo '<p>' . $license['status'] . '</p>';
			echo '<p>' . $license['expiration'] . '</p>';
			echo '<p>' . $license['renew_uri'] . '</p>';
		endforeach;
	endforeach; endif;

endif; ?>
```



## Limiting Dashboard Access

There's also two functions that limit subscriber access to the actual WordPress dashboard. You'll need to develop this a bit more if you also want to have a custom log in screen and direct users to your account page.

### Disabled Admin Bar

```
/**
 * Disables the #wpadmin bar for users without "edit_posts" permissions.
 */
 function edd_account_admin_bar() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		add_filter( 'show_admin_bar', '__return_false' );
	}
}
add_action( 'after_setup_theme', 'edd_account_admin_bar' );
```

### Disables Dashboard Access

```
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
```