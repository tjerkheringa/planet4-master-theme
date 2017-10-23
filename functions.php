<?php

if ( ! class_exists( 'Timber' ) ) {
	add_action(
		'admin_notices', function() {
			printf( '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="%s">Plugins menu</a></p></div>',
				esc_url( admin_url( 'plugins.php#timber' ) )
			);
		}
	);

	add_filter(
		'template_include', function( $template ) {
			return get_stylesheet_directory() . '/static/no-timber.html';
		}
	);

	return;
}

/**
 * Function and filter required from the WP SAML plugin.
 * Instructions at: https://wordpress.org/plugins/wp-saml-auth/#installation .
 */
function wpsax_filter_option( $value, $option_name ) {
	$defaults = array(
		/**
		 * Type of SAML connection bridge to use.
		 *
		 * 'internal' uses OneLogin bundled library; 'simplesamlphp' uses SimpleSAMLphp.
		 *
		 * Defaults to SimpleSAMLphp for backwards compatibility.
		 *
		 * @param string
		 */
		'connection_type' => 'simplesamlphp',
		/**
		 * Configuration options for OneLogin library use.
		 *
		 * See comments with "Required:" for values you absolutely need to configure.
		 *
		 * @param array
		 */
		'internal_config'        => array(
			// Validation of SAML responses is required.
			'strict'       => true,
			'debug'        => defined( 'WP_DEBUG' ) && WP_DEBUG ? true : false,
			'baseurl'      => home_url(),
			'sp'           => array(
				'entityId' => 'urn:' . parse_url( home_url(), PHP_URL_HOST ),
				'assertionConsumerService' => array(
					'url'  => home_url(),
					'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
				),
			),
			'idp'          => array(
				// Required: Set based on provider's supplied value.
				'entityId' => '',
				'singleSignOnService' => array(
					// Required: Set based on provider's supplied value.
					'url'  => '',
					'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
				),
				'singleLogoutService' => array(
					// Required: Set based on provider's supplied value.
					'url'  => '',
					'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
				),
				// Required: Contents of the IDP's public x509 certificate.
				// Use file_get_contents() to load certificate contents into scope.
				'x509cert' => '',
				// Optional: Instead of using the x509 cert, you can specify the fingerprint and algorithm.
				'certFingerprint' => '',
				'certFingerprintAlgorithm' => '',
			),
		),
		/**
		 * Path to SimpleSAMLphp autoloader.
		 *
		 * Follow the standard implementation by installing SimpleSAMLphp
		 * alongside the plugin, and provide the path to its autoloader.
		 * Alternatively, this plugin will work if it can find the
		 * `SimpleSAML_Auth_Simple` class.
		 *
		 * @param string
		 */
		'simplesamlphp_autoload' => dirname( __FILE__ ) . '/simplesamlphp/lib/_autoload.php',
		/**
		 * Authentication source to pass to SimpleSAMLphp
		 *
		 * This must be one of your configured identity providers in
		 * SimpleSAMLphp. If the identity provider isn't configured
		 * properly, the plugin will not work properly.
		 *
		 * @param string
		 */
		'auth_source'            => 'default-sp',
		/**
		 * Whether or not to automatically provision new WordPress users.
		 *
		 * When WordPress is presented with a SAML user without a
		 * corresponding WordPress account, it can either create a new user
		 * or display an error that the user needs to contact the site
		 * administrator.
		 *
		 * @param bool
		 */
		'auto_provision'         => true,
		/**
		 * Whether or not to permit logging in with username and password.
		 *
		 * If this feature is disabled, all authentication requests will be
		 * channeled through SimpleSAMLphp.
		 *
		 * @param bool
		 */
		'permit_wp_login'        => true,
		/**
		 * Attribute by which to get a WordPress user for a SAML user.
		 *
		 * @param string Supported options are 'email' and 'login'.
		 */
		'get_user_by'            => 'email',
		/**
		 * SAML attribute which includes the user_login value for a user.
		 *
		 * @param string
		 */
		'user_login_attribute'   => 'uid',
		/**
		 * SAML attribute which includes the user_email value for a user.
		 *
		 * @param string
		 */
		'user_email_attribute'   => 'mail',
		/**
		 * SAML attribute which includes the display_name value for a user.
		 *
		 * @param string
		 */
		'display_name_attribute' => 'display_name',
		/**
		 * SAML attribute which includes the first_name value for a user.
		 *
		 * @param string
		 */
		'first_name_attribute' => 'first_name',
		/**
		 * SAML attribute which includes the last_name value for a user.
		 *
		 * @param string
		 */
		'last_name_attribute' => 'last_name',
		/**
		 * Default WordPress role to grant when provisioning new users.
		 *
		 * @param string
		 */
		'default_role'           => get_option( 'default_role' ),
	);
	$value = isset( $defaults[ $option_name ] ) ? $defaults[ $option_name ] : $value;
	return $value;
}
add_filter( 'wp_saml_auth_option', 'wpsax_filter_option', 10, 2 );


/**
 * Class P4_Master_Site.
 * The main class that handles Planet4 Master Theme.
 */
class P4_Master_Site extends TimberSite {

	/** @var string $theme_dir */
	protected $theme_dir;
	/** @var string $theme_images_dir */
	protected $theme_images_dir;
	/** @var array $websites */
	protected $websites = [
		'en_US' => 'International (English)',
		'el_GR' => 'Greece (Ελληνικά)',
	];
	/** @var array $child_css */
	protected $child_css = array();

	/**
	 * P4_Master_Site constructor.
	 */
	public function __construct() {

		$this->settings();
		add_theme_support( 'post-formats' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_post_type_support( 'page', 'excerpt' );  // Added excerpt option to pages.

		add_filter( 'timber_context', array( $this, 'add_to_context' ) );
		add_filter( 'get_twig', array( $this, 'add_to_twig' ) );

		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );
		add_action( 'cmb2_admin_init', array( $this, 'register_header_metabox' ) );
		add_action( 'pre_get_posts', array( $this, 'tags_support_query' ) );
		add_action( 'admin_init', array( $this, 'add_copyright_text' ) );
		add_action( 'admin_init', array( $this, 'add_google_tag_manager_identifier_setting' ) );

		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_parent_styles' ) );
		register_nav_menus( array(
		    'navigation-bar-menu' => __( 'Navigation Bar Menu', 'planet4-master-theme' )
        ) );
		parent::__construct();
	}

	/**
	 * Show copyright text field.
	 *
	 * @param array $args
	 */
	public function copyright_show_settings( $args ) {
		$copyright = get_option( 'copyright', '' );

		printf(
			'<input type="text" name="copyright" class="regular-text" value="%1$s" id="%2$s" />',
			esc_attr( $copyright ),
			esc_attr( $args['label_for'] )
		);
	}

	/**
	 * Show google tag manager identifier text field.
	 *
	 * @param array $args
	 */
	public function google_tag_show_settings( $args ) {
		$google_tag_identifier = get_option( 'google_tag_manager_identifier', '' );

		printf(
			'<input type="text" name="google_tag_manager_identifier" class="regular-text" value="%1$s" id="%2$s" />',
			esc_attr( $google_tag_identifier ),
			esc_attr( $args['label_for'] )
		);
	}

	/**
	 * Function to add copyright text block in general options
	 */
	public function add_copyright_text() {
		add_settings_section(
			'copyrighttext_id',
			'',
			'',
			'general'
		);

		// Register taxonomies for page.
		register_setting(
			'general',
			'copyright',
			'trim'
		);

		// Register the field for the "copyright" section.
		add_settings_field(
			'copyright',
			'Copyright Text',
			array( $this, 'copyright_show_settings' ),
			'general',
			'copyrighttext_id',
			array(
				'label_for' => 'copyrighttext_id',
			)
		);
	}

	/**
	 * Function to add google tag manager identifier block in general options
	 */
	public function add_google_tag_manager_identifier_setting() {

		// Add google tag manager identifier section.
		add_settings_section(
			'google_tag_manager_identifier',
			'',
			'',
			'general'
		);

		// Register google tag manager identifier setting.
		register_setting(
			'general',
			'google_tag_manager_identifier',
			'trim'
		);

		// Register the field for the "google tag manager identifier" section.
		add_settings_field(
			'google_tag_manager_identifier',
			'Google Tag Manager Identifier',
			array( $this, 'google_tag_show_settings' ),
			'general',
			'google_tag_manager_identifier',
			array(
				'label_for' => 'google_tag_manager_identifier',
			)
		);
	}

  	/*
	* Define settings for the Planet4 Master Theme.
	*/
	protected function settings() {
		Timber::$autoescape = true;
		Timber::$dirname = array( 'templates', 'views' );
		$this->theme_dir = get_template_directory_uri();
		$this->theme_images_dir = $this->theme_dir . '/images/';
	}

	/**
	 * Load styling and behaviour.
	 */
	public function enqueue_parent_styles() {
		wp_enqueue_style( 'bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css', array(), '4.0.0-alpha.6' );
		wp_enqueue_style( 'parent-style', $this->theme_dir . '/style.css' );
		wp_register_script( 'jquery', 'https://code.jquery.com/jquery-3.2.1.min.js', array(), null, true );
		wp_enqueue_script( 'bootstrapjs', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js', array(), '4.0.0-beta', true );
		wp_enqueue_script( 'main', $this->theme_dir . '/assets/js/main.js', array( 'jquery' ), null, true );
	}

	/**
	 * Registers custom post types.
	 */
	public function register_post_types() {}

	/**
	 * Registers taxonomies.
	 */
	public function register_taxonomies() {
		register_taxonomy_for_object_type( 'post_tag', 'page' );
		register_taxonomy_for_object_type( 'category', 'page' );
	}

	/**
	 * Include tags and categories when querying.
	 *
	 * @param WP_Query $wp_query The WP_Query object.
	 */
	public function tags_support_query( $wp_query ) {
		if ( $wp_query->get( 'tag' ) ) {
			$wp_query->set( 'post_type', 'any' );
		}

		if ( $wp_query->get( 'category_name' ) ) {
			$wp_query->set( 'post_type', 'any' );
		}
	}

	/**
	 * Hook in and add a Theme metabox. Can only happen on the 'cmb2_admin_init' or 'cmb2_init' hook.
	 */
	public function register_header_metabox() {

		$prefix = 'p4_';

		$p4_header = new_cmb2_box( array(
			'id'            => $prefix . 'metabox',
			'title'         => __( 'Page Header Fields', 'planet4-master-theme' ),
			'object_types'  => array( 'page' ), // Post type.
		) );

		$p4_header->add_field( array(
			'name' => __( 'Header Title', 'planet4-master-theme' ),
			'desc' => __( 'Header title comes here', 'planet4-master-theme' ),
			'id'   => $prefix . 'title',
			'type' => 'text_medium',
		) );

		$p4_header->add_field(
			array(
				'name' => __( 'Header Subtitle', 'planet4-master-theme' ),
				'desc' => __( 'Header subtitle comes here', 'planet4-master-theme' ),
				'id'   => $prefix . 'subtitle',
				'type' => 'text_medium',
			)
		);

		$p4_header->add_field(
			array(
				'name'    => __( 'Header Description', 'planet4-master-theme' ),
				'desc'    => __( 'Header description comes here', 'planet4-master-theme' ),
				'id'      => $prefix . 'description',
				'type'    => 'wysiwyg',
				'options' => array(
					'textarea_rows' => 5,
				),
			)
		);

		$p4_header->add_field(
			array(
				'name' => __( 'Header Button Title', 'planet4-master-theme' ),
				'desc' => __( 'Header button title comes here', 'planet4-master-theme' ),
				'id'   => $prefix . 'button_title',
				'type' => 'text_medium',
			)
		);

		$p4_header->add_field(
			array(
				'name' => __( 'Header Button Link', 'planet4-master-theme' ),
				'desc' => __( 'Header button link comes here', 'planet4-master-theme' ),
				'id'   => $prefix . 'button_link',
				'type' => 'text_medium',
			)
		);
	}

	/**
	 * Adds more data to the context variable that will be passed to the main template.
	 *
	 * @param array $context The associative array with data to be passed to the main template.
	 *
	 * @return mixed
	 */
	public function add_to_context( $context ) {
		$context['data_nav_bar'] = [
			'websites'     => $this->websites,
			'images'       => $this->theme_images_dir,
			'home_url'     => home_url( '/' ),
			'act_url'      => '/act',
			'explore_url'  => '/explore',
			'search_query' => get_search_query(),
		];

		$context['foo']  = 'bar';   // For unit test purposes.
		$context['domain'] = 'planet4-master-theme';
		$context['site'] = $this;
		$context['navbar_menu'] = new TimberMenu('navigation-bar-menu');

		return $context;
	}

	/**
	 * Add your own functions to Twig.
	 *
	 * @param Twig_ExtensionInterface $twig The Twig object that implements the Twig_ExtensionInterface.
	 *
	 * @return mixed
	 */
	public function add_to_twig( $twig ) {
		$twig->addExtension( new Twig_Extension_StringLoader() );
		return $twig;
	}
}

new P4_Master_Site();
