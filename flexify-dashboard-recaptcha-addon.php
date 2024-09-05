<?php

/**
 * Plugin Name: 			Flexify Dashboard - reCAPTCHA addon
 * Description: 			Extensão que adiciona uma camada extra de proteção no login de administrador do WordPress, prevenindo ataques por bots, exclusivo para o Flexify Dashboard.
 * Plugin URI: 				https://meumouse.com/plugins/flexify-dashboard/
 * Author: 					MeuMouse.com
 * Author URI: 				https://meumouse.com/
 * Version: 				1.0.0
 * Requires PHP: 			7.4
 * Tested up to:      		6.6.1
 * Text Domain: 			flexify-dashboard-recaptcha-addon
 * Domain Path: 			/languages
 * License: 				GPL2
 */

namespace MeuMouse\Flexify_Dashboard\Recaptcha;

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Class for extends Flexify_Dashboard main class
 *
 * @since 1.0.0
 * @version 1.0.0
 * @package MeuMouse.com
 */
class Recaptcha {
	/**
	 * Recaptcha The single instance of Recaptcha.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	private static $instance = null;

	/**
	 * The slug
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $slug = 'flexify-dashboard-recaptcha-addon';

	/**
	 * Plugin version number
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public static $version = '1.0.0';

	/**
	 * Constructor function
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		$this->setup_constants();

		add_action( 'init', array( $this, 'load_plugin_textdomain' ), -1 );
		add_action( 'plugins_loaded', array( $this, 'load_checker' ), 5 );
	}
	

	/**
	 * Check requeriments on plugins_loaded hook
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function load_checker() {
		if ( ! function_exists('is_plugin_active') ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( is_plugin_active('flexify-dashboard/flexify-dashboard.php') ) {
			add_action( 'plugins_loaded', array( $this, 'setup_includes' ), 999 );
			add_filter( 'plugin_action_links_' . FLEXIFY_DASHBOARD_RECAPTCHA_BASENAME, array( $this, 'add_action_links' ), 10, 4 );
			add_filter( 'plugin_row_meta', array( $this, 'add_row_meta_links' ), 10, 4 );
		} else {
			deactivate_plugins('flexify-dashboard-recaptcha-addon/flexify-dashboard-recaptcha-addon.php');
			add_action( 'admin_notices', array( $this, 'require_flexify_dashboard_notice' ) );
		}

		// check if WooCommerce is active
		if ( is_plugin_active('woocommerce/woocommerce.php') ) {
			add_action( 'before_woocommerce_init', array( $this, 'setup_hpos_compatibility' ) );
		}
	}


	/**
	 * Setup WooCommerce High-Performance Order Storage (HPOS) compatibility
	 * 
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return void
	 */
	public function setup_hpos_compatibility() {
		if ( defined('WC_VERSION') && version_compare( WC_VERSION, '7.1', '>' ) ) {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
					'custom_order_tables', FLEXIFY_DASHBOARD_RECAPTCHA_FILE, true
				);
			}
		}
	}


	/**
	 * Ensures only one instance of Recaptcha is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @see Recaptcha()
	 * @return Main Recaptcha instance
	 */
	public static function run() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Define constant if not already set
	 *
	 * @since 1.0.0
	 * @param string $name | Constant name
	 * @param string|bool $value | Constant value
	 * @return void
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}


	/**
	 * Setup plugin constants
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function setup_constants() {
		$this->define( 'FLEXIFY_DASHBOARD_RECAPTCHA_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'FLEXIFY_DASHBOARD_RECAPTCHA_DIR', plugin_dir_path( __FILE__ ) );
		$this->define( 'FLEXIFY_DASHBOARD_RECAPTCHA_INC_DIR', FLEXIFY_DASHBOARD_RECAPTCHA_DIR . '/inc/' );
		$this->define( 'FLEXIFY_DASHBOARD_RECAPTCHA_URL', plugin_dir_url( __FILE__ ) );
		$this->define( 'FLEXIFY_DASHBOARD_RECAPTCHA_FILE', __FILE__ );
		$this->define( 'FLEXIFY_DASHBOARD_RECAPTCHA_ABSPATH', dirname( FLEXIFY_DASHBOARD_RECAPTCHA_FILE ) . '/' );
		$this->define( 'FLEXIFY_DASHBOARD_RECAPTCHA_SLUG', self::$slug );
		$this->define( 'FLEXIFY_DASHBOARD_RECAPTCHA_VERSION', self::$version );
		$this->define( 'FLEXIFY_DASHBOARD_RECAPTCHA_DISABLED', false );
	}


	/**
	 * Setup includes based on predefined priorities
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function setup_includes() {
		$includes = array(
			'classes/class-core.php',
			'classes/class-admin.php',
			'classes/class-updater.php',
		);

		foreach ( $includes as $file ) {
			$file_path = FLEXIFY_DASHBOARD_RECAPTCHA_INC_DIR . $file;

			if ( file_exists( $file_path ) ) {
				include_once $file_path;
			}
		}
	}


	/**
	 * Require Flexify Dashboard plugin notice
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function require_flexify_dashboard_notice() {
		$class = 'notice notice-error is-dismissible';
		$message = __( 'O plugin <strong>Flexify Dashboard - reCAPTCHA addon</strong> necessita do <strong>Flexify Dashboard</strong> para funcionar corretamente, realize a instalação e ativação antes de ativar este.', 'flexify-dashboard-recaptcha-addon' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}


	/**
	 * Plugin action links
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function add_action_links( $action_links ) {
		$plugins_links = array(
			'<a href="' . admin_url( 'admin.php?page=flexify-dashboard' ) . '">'. __( 'Configurar', 'flexify-dashboard-recaptcha-addon' ) .'</a>',
		);

		return array_merge( $plugins_links, $action_links );
	}


	/**
	 * Add meta links on plugin
	 * 
	 * @since 1.0.0
	 * @param string $plugin_meta | An array of the plugin’s metadata, including the version, author, author URI, and plugin URI
	 * @param string $plugin_file | Path to the plugin file relative to the plugins directory
	 * @param array $plugin_data | An array of plugin data
	 * @param string $status | Status filter currently applied to the plugin list
	 * @return string
	 */
	public function add_row_meta_links( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( strpos( $plugin_file, FLEXIFY_DASHBOARD_RECAPTCHA_BASENAME ) !== false ) {
			$new_links = array(
				'docs' => '<a href="'. FLEXIFY_DASHBOARD_DOCS_LINK .'" target="_blank">'. __( 'Documentação', 'flexify-dashboard-recaptcha-addon' ) .'</a>',
			);
			
			$plugin_meta = array_merge( $plugin_meta, $new_links );
		}
	
		return $plugin_meta;
	}


	/**
	 * Load the plugin text domain for translation
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain( 'flexify-dashboard-recaptcha-addon', false, dirname( FLEXIFY_DASHBOARD_RECAPTCHA_BASENAME ) . '/languages/' );
	}


	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Trapaceando?', 'flexify-dashboard-recaptcha-addon' ), '1.0.0' );
	}


	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Trapaceando?', 'flexify-dashboard-recaptcha-addon' ), '1.0.0' );
	}
}

/**
 * Initialise the plugin
 */
Recaptcha::run();