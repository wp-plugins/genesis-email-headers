<?php

/**
 * Plugin Name: Genesis Email Headers
 * Plugin URI: https://github.com/SkyDriver/genesis-email-headers
 * Description: Genesis Email Headers will allow you to customize email sender headers.
 * Version: 1.0.0
 * Author: Damjan Krstevski
 * Author URI: https://mk.linkedin.com/in/krstevskidamjan
 * Text Domain: genesis_email_headers
 * License: GPL2
 */



/*  Copyright 2015  Damjan Krstevski  (email : krstevski[dot]damjan[at]live[dot]com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/








/**
 *	Class Genesis_Email_Headers
 *
 *	@author Damjan Krstevski
 *	@since 1.0.0
 */
class Genesis_Email_Headers
{
	const VERSION = '1.0.0';
	const TEXTDOMAIN = 'genesis_email_headers';

	const EMAIL_FORM_KEY = 'genesis_mail_form';
	const EMAIL_NAME_KEY = 'genesis_mail_name';

	const REQUIRED_GENESIS_VERSION = '2.1.0';





	/**
	 *	Object Constructor
	 *
	 *	@since 1.0.0
	 *	@access public
	 **/
	public function __construct()
	{
		register_activation_hook( __FILE__, array($this, 'activation_hook') );

		add_filter( 'genesis_theme_settings_defaults', array(&$this, 'register_defaults') );
		add_action( 'genesis_settings_sanitizer_init', array(&$this, 'sanitization_filters') );
		add_action( 'genesis_theme_settings_metaboxes', array(&$this, 'settings_box') );

		add_filter('wp_mail_from', array(&$this, 'email_filter'),10, 1);
		add_filter('wp_mail_from_name', array(&$this, 'name_filter'), 10, 1);

		add_action( 'wp_ajax_send_test_email', array(&$this, 'send_test_email_callback') );
		add_action( 'admin_footer', array(&$this, 'send_test_email_js') );
	} // End of object constructor.





	public function activation_hook()
	{
		if ( ! defined( 'PARENT_THEME_VERSION' ) || ! version_compare( PARENT_THEME_VERSION, self::REQUIRED_GENESIS_VERSION, '>=' ) )
		{
			deactivate_plugins( plugin_basename( __FILE__ ) ); /** Deactivate ourself */
			wp_die(sprintf(__( 'Sorry, you cannot activate without Genesis %s or greater', self::TEXTDOMAIN ), self::REQUIRED_GENESIS_VERSION ) );
		}
	} // End of function activation_hook()





	public function send_test_email_callback()
	{
		$email_to 	= get_option('admin_email');
		$subject 	= __('Genesis Email Headers - Test Email', self::TEXTDOMAIN);
		$body 		= __('Genesis Email Headers Test Message, Thanks for using this plugin.', self::TEXTDOMAIN);

		$status = wp_mail( $email_to, $subject, $body );

		$message = __('Error while sending an email, please contact the hosting administrator.', self::TEXTDOMAIN);

		if( $status )
			$message = __('Success. Check your email address (and junk folder).', self::TEXTDOMAIN);

		echo json_encode(array('status' => $status, 'message' => $message));

		wp_die();
	} // End of function send_test_email_callback()





	public function send_test_email_js()
	{
		?>
		<script type="text/javascript" >
		jQuery(document).ready(function($) {

			var geh_test = $('body').find('a.genesis-email-header-test');
			geh_test.on('click', function(e) {
				e.preventDefault();

				$.post(ajaxurl, {'action': 'send_test_email'}, function(response) {
					if( response )
					{
						var obj = jQuery.parseJSON( response );
						var color = obj.status ? 'green' : 'red';
						geh_test.parent('div').html('<span style="color: ' + color + ';">' + obj.message + '</span>');
					}
				});
			});

		});
		</script>
		<?php
	} // End of function send_test_email_js()





	/**
	 * Register Defaults
	 */
	public function register_defaults( $defaults )
	{
		$defaults[self::EMAIL_FORM_KEY] = '';
		$defaults[self::EMAIL_NAME_KEY] = '';

		return $defaults;
	} // End of function register_defaults( $defaults )





	/**
	 * Sanitization
	 */
	public function sanitization_filters()
	{
		genesis_add_option_filter(
			'email_address',
			GENESIS_SETTINGS_FIELD,
			array(
				self::EMAIL_FORM_KEY,
			)
		);

		genesis_add_option_filter(
			'no_html',
			GENESIS_SETTINGS_FIELD,
			array(
				self::EMAIL_NAME_KEY,
			)
		);
	} // End of function sanitization_filters()





	/**
	 * Register Metabox
	 */
	public function settings_box( $_genesis_theme_settings_pagehook )
	{
		add_meta_box(
			'genesis-email-header-settings',
			__('Sender Email Headers', self::TEXTDOMAIN),
			array($this, 'settings_fields'),
			$_genesis_theme_settings_pagehook,
			'main',
			'high'
		);
	} // End of function settings_box( $_genesis_theme_settings_pagehook )





	/**
	 * Create Metabox
	 */
	public function settings_fields()
	{

		printf(
			'<p>%s:<br />
			<span class="description">(%s: %s)</span><br />',
			__('Email Form', self::TEXTDOMAIN),
			__('Example', self::TEXTDOMAIN),
			get_option('admin_email')
		);

		printf(
			'<input type="text" name="%s[%s]" value="%s" size="50" placeholder="email@address.com" /> </p>',
			GENESIS_SETTINGS_FIELD,
			self::EMAIL_FORM_KEY,
			sanitize_email( genesis_get_option(self::EMAIL_FORM_KEY) )
		);


		printf(
			'<p>%s:<br />
			<span class="description">(%s: %s)</span><br />',
			__('Email Name', self::TEXTDOMAIN),
			__('Example', self::TEXTDOMAIN),
			get_option('blogname')
		);

		printf(
			'<input type="text" name="%s[%s]" value="%s" size="50" placeholder="%s" /> </p>',
			GENESIS_SETTINGS_FIELD,
			self::EMAIL_NAME_KEY,
			esc_attr( genesis_get_option(self::EMAIL_NAME_KEY) ),
			__('Email Sender Name', self::TEXTDOMAIN)
		);


		printf(
			'<div>
				<a href="#" title="%s" class="genesis-email-header-test">%s</a>
				&nbsp;
				<span class="description">(%s %s)</span>
			</div>',
			__('Click here to send test email', self::TEXTDOMAIN),
			__('Send Test Email', self::TEXTDOMAIN),
			__('The Email will be sent to'),
			get_option('admin_email')
		);
	} // End of function settings_fields()





	public function email_filter( $email )
	{
		$email = get_option('admin_email');

		$new_email = genesis_get_option(self::EMAIL_FORM_KEY);
		if( !empty($new_email) && is_email($new_email) )
			return apply_filters('genesis_email_headers_form', $new_email);

		return $email;
	} // End of function email_filter( $email )





	public function name_filter( $name )
	{
		$name = get_option('blogname');

		$new_name = genesis_get_option(self::EMAIL_NAME_KEY);
		if( !empty($new_name) )
			return apply_filters('genesis_email_headers_name', esc_attr($new_name));

		return $name;
	} // End of function name_filter( $name )


} // End of Class Genesis_Email_Headers.




/**
 *	Run Genesis Email Header only on genesis theme settings page
 **/

add_action('admin_init', 'genesis_email_headers_init');
function genesis_email_headers_init()
{
	$Genesis_Email_Headers = new Genesis_Email_Headers;
}

?>