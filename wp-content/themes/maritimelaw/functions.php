<?php

/**
 * We should push this part off to that TGM plugin activator thing.. probably.
 * For now, we'll keep this.
 */

if ( ! class_exists( 'Timber' ) ) {
	add_action( 'admin_notices', function() {
		echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php') ) . '</a></p></div>';
	});
	
	add_filter('template_include', function($template) {
		return get_stylesheet_directory() . '/static/no-timber.html';
	});

	return;
}

/**
 * This will define the Directory for Timber.
 * I'll keep it the default 'templates' for now.
 */
Timber::$dirname = array('templates', 'views');

/**
 * Class StarterSite
 * This is what gets called at the top of the site, so let's keep this.
 */
class StarterSite extends TimberSite {

	/**
	 * StarterSite constructor.
	 * Looks like this is what adds the theme support stuff, pretty cool.
	 *
	 */
	function __construct() {

		add_theme_support( 'post-formats' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'menus' );
		add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );
		add_filter( 'timber_context', array( $this, 'add_to_context' ) );
		add_filter( 'get_twig', array( $this, 'add_to_twig' ) );

		/**
		 * Custom Post Type Registration
		 */
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_taxonomies' ) );

		/**
		 * This section will register all the menu locations we have
		 */
		add_action( 'init', array( $this, 'register_menus' ) );

		/**
		 * This section will add any shortcodes we need
		 */
		add_action( 'init', array($this, 'register_shortcodes') );

		/**
		 * This section will register all the sidebars our theme will have
		 */
		add_action( 'widgets_init', array( $this, 'register_sidebars' ) );

		/**
		 * This section will register any AJAX URLs
		 */
		// add_action( 'init', array($this, 'register_ajax_codes') );
		add_action('wp_ajax_nopriv_claimcalculator', array( $this, 'claim_calculator_ajax' ) );
		add_action('wp_ajax_claimcalculator', array( $this, 'claim_calculator_ajax' ) );

		add_action('wp_ajax_nopriv_jallooper', array( $this, 'infinite_scroller_ajax') );
		add_action('wp_ajax_jallooper', array( $this, 'infinite_scroller_ajax') );

		add_action('wp_ajax_nopriv_dosearch', array( $this, 'search_posts_ajax') );
		add_action('wp_ajax_dosearch', array( $this, 'search_posts_ajax') );


		parent::__construct();
	}

	/**
	 * Register Shortcodes -- Maritime Theme
	 */
	function register_shortcodes()
	{
		add_shortcode( 'final_results', array($this,'register_quiz_results_shortcode') );
		add_shortcode( 'maritime_claim_calculator', array($this,'register_quiz_widget_shortcode') );
		add_shortcode( 'faq_tabs', array($this,'register_faq_widget_shortcode') );
	}

	/**
	 * Register any AJAX actions
	 */
	function register_ajax_codes()
	{
		add_action('wp_ajax_nopriv_claimcalculator', array( $this, 'claim_calculator_ajax' ) );
	}

	/**
	 * Register Sidebars -- Maritime Theme
	 */
	function register_sidebars()
	{
		# Footer Sidebar, A
		register_sidebar( array(
			'name' => __( 'Footer Sidebar (A)', 'theme-slug' ),
			'id' => 'footer-sidebar-a',
			'description' => __( 'Widgets in this area will be shown on the (A) Side of the footer.', 'theme-slug' ),
			'before_widget' => '<div id="%1$s" class="jal-any-footer-sidebar %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<span class="widgettitle">',
			'after_title'   => '</span>',
		) );

		# Footer Sidebar, B
		register_sidebar( array(
			'name' => __( 'Footer Sidebar (B)', 'theme-slug' ),
			'id' => 'footer-sidebar-b',
			'description' => __( 'Widgets in this area will be shown on the (B) Side of the footer.', 'theme-slug' ),
			'before_widget' => '<div id="%1$s" class="jal-any-footer-sidebar %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<span class="widgettitle">',
			'after_title'   => '</span>',
		) );

		# Footer Sidebar, B
		register_sidebar( array(
			'name' => __( 'Footer Sidebar (Contact)', 'theme-slug' ),
			'id' => 'footer-sidebar-c',
			'description' => __( 'Widgets in this area will be shown on the Far Right Side of the footer.', 'theme-slug' ),
			'before_widget' => '<div id="%1$s" class="jal-any-footer-sidebar %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<span class="widgettitle">',
			'after_title'   => '</span>',
		) );

		# Logos Section
		register_sidebar( array(
			'name' => __( 'Footer Sidebar (Logos)', 'theme-slug' ),
			'id' => 'footer-sidebar-logos',
			'description' => __( 'Widgets in this area will be shown on the logos section, above the footer.', 'theme-slug' ),
			'before_widget' => '<li>',
			'after_widget'  => '</li>',
			'before_title'  => '<span class="hidden">',
			'after_title'   => '</span>',
		) );

		# Post Sidebar (primary)
		register_sidebar( array(
			'name' => __( 'Post Sidebar (primary)', 'theme-slug' ),
			'id' => 'primary-sidebar',
			'description' => __( 'Widgets in this area will be shown on the primary floating sidebar', 'theme-slug' ),
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '<span>',
			'after_title'   => '</span>',
		) );

		# Post Sidebar (secondary)
		register_sidebar( array(
			'name' => __( 'Post Sidebar (secondary)', 'theme-slug' ),
			'id' => 'secondary-sidebar',
			'description' => __( 'Widgets in this area will be shown on the secondary floating sidebar', 'theme-slug' ),
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '<span>',
			'after_title'   => '</span>',
		) );

		# Mega-Menu, Global
		register_sidebar( array(
			'name' => __( 'Mega-Menu Sidebar', 'theme-slug' ),
			'id' => 'mega-menu-sidebar',
			'description' => __( 'Widgets in this area will be shown on the mega-menu when opening each link', 'theme-slug' ),
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '<span class="hidden">',
			'after_title'   => '</span>',
		) );

		add_post_type_support( 'post', 'simple-page-sidebars' );
	}

	/**
	 * Register Menus -- Maritime Theme
	 */
	function register_menus()
	{
		# Desktop, header menu
		register_nav_menu('main_header_menu', 'This is the menu that will appear on the top-most menu bar.');
		register_nav_menu('main_mobile_menu', 'This is the menu that will appear on the top-most menu bar, in mobile devices.');
		register_nav_menu('homepage_menu', 'This menu appears on the homepage, in the middle block.');
	}

	/**
	 * Register Post Types
	 */
	function register_post_types() {

		/**
		 * Register any base 'page' features
		 */
		add_post_type_support( 'page', 'excerpt' );
		add_post_type_support( 'page', 'thumbnail' );

		/**
		 * Register all the CPT methods we have locally
		 */

		# ATTORNEY CPT
		$this->register_attorney_cpt();

		# PRACTICE AREA CPT
		// $this->register_practice_area_cpt();

		# TESTIMONIAL CPT
		$this->register_testimonial_cpt();

		# FAQ CPT
		$this->register_faq_cpt();

		# STAFF CPT
		$this->register_staff_cpt();

		# CASE RESULT CPT
		$this->register_case_result_cpt();

		# NEWS CPT
		// $this->register_news_cpt();

		# VIDEO CPT
		// $this->register_video_cpt();

		# CONTENT SNIPPET
		$this->register_content_cpt();

		# LEGACY CPT
		$this->register_legacy_cpt();

	}

	/**
	 * Register Custom Taxonomies
	 */
	function register_taxonomies() {
		# FAQ Type Taxonomy
		$this->register_faq_type_tax();

		# Case Result Tags
		$this->register_case_result_tag_tax();

		# Case Result (Claim Types)
		$this->register_case_result_claim_type_tax();

		# Video Specific Categories
		$this->register_video_category_tax();
	}

	/**
	 * Post Types Methods
	 */
	function register_attorney_cpt()
	{
		// Set up the arguments for the post type.
		$args = array(
			// A short description of what your post type is. As far as I know, this isn't used anywhere
			// in core WordPress.  However, themes may choose to display this on post type archives.
			'description'         => __( 'Attorney Pages', 'example-textdomain' ), // string
			// Whether the post type should be used publicly via the admin or by front-end users.  This
			// argument is sort of a catchall for many of the following arguments.  I would focus more
			// on adjusting them to your liking than this argument.
			'public'              => true, // bool (default is FALSE),
			// Shows it in the REST API
			'show_in_rest'			=> true,
			// Whether queries can be performed on the front end as part of parse_request().
			'publicly_queryable'  => true, // bool (defaults to 'public').
			// Whether to exclude posts with this post type from front end search results.
			'exclude_from_search' => false, // bool (defaults to the opposite of 'public' argument)
			// Whether individual post type items are available for selection in navigation menus.
			'show_in_nav_menus'   => true, // bool (defaults to 'public')
			// Whether to generate a default UI for managing this post type in the admin. You'll have
			// more control over what's shown in the admin with the other arguments.  To build your
			// own UI, set this to FALSE.
			'show_ui'             => true, // bool (defaults to 'public')
			// Whether to show post type in the admin menu. 'show_ui' must be true for this to work.
			// Can also set this to a string of a top-level menu (e.g., 'tools.php'), which will make
			// the post type screen be a sub-menu.
			'show_in_menu'        => true, // bool (defaults to 'show_ui')
			// Whether to make this post type available in the WordPress admin bar. The admin bar adds
			// a link to add a new post type item.
			'show_in_admin_bar'   => true, // bool (defaults to 'show_in_menu')
			// The position in the menu order the post type should appear. 'show_in_menu' must be true
			'menu_position'       => null, // int (defaults to 25 - below comments)
			// The URI to the icon to use for the admin menu item or a dashicon class. See:
			// https://developer.wordpress.org/resource/dashicons/
			'menu_icon'           => 'dashicons-groups', // string (defaults to use the post icon)
			// Whether the posts of this post type can be exported via the WordPress import/export plugin
			// or a similar plugin.
			'can_export'          => true, // bool (defaults to TRUE)
			// Whether to delete posts of this type when deleting a user who has written posts.
			'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')
			// Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts.
			'hierarchical'        => false, // bool (defaults to FALSE)
			// Whether the post type has an index/archive/root page like the "page for posts" for regular
			// posts. If set to TRUE, the post type name will be used for the archive slug.  You can also
			// set this to a string to control the exact name of the archive slug.
			'has_archive'         => false, // bool|string (defaults to FALSE)
			// Sets the query_var key for this post type. If set to TRUE, the post type name will be used.
			// You can also set this to a custom string to control the exact key.
			'query_var'           => true, // bool|string (defaults to TRUE - post type name)
			// A string used to build the edit, delete, and read capabilities for posts of this type. You
			// can use a string or an array (for singular and plural forms).  The array is useful if the
			// plural form can't be made by simply adding an 's' to the end of the word.  For example,
			// array( 'box', 'boxes' ).
			'capability_type'     => 'post', // string|array (defaults to 'post')
			// Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for
			// you.  If set to FALSE, you'll need to roll your own handling of this by filtering the
			// 'map_meta_cap' hook.
			'map_meta_cap'        => true, // bool (defaults to FALSE)
			// How the URL structure should be handled with this post type.  You can set this to an
			// array of specific arguments or true|false.  If set to FALSE, it will prevent rewrite
			// rules from being created.
			'rewrite' => array(
				// The slug to use for individual posts of this type.
				'slug'       => 'attorneys', 'with_front' => false// string (defaults to the post type name)
				/*// Whether to show the $wp_rewrite->front slug in the permalink.
				'with_front' => false, // bool (defaults to TRUE)
				// Whether to allow single post pagination via the <!--nextpage--> quicktag.
				'pages'      => true, // bool (defaults to TRUE)
				// Whether to create pretty permalinks for feeds.
				'feeds'      => true, // bool (defaults to the 'has_archive' argument)
				// Assign an endpoint mask to this permalink.
				'ep_mask'    => EP_PERMALINK, // const (defaults to EP_PERMALINK)*/
			),
			// What WordPress features the post type supports.  Many arguments are strictly useful on
			// the edit post screen in the admin.  However, this will help other themes and plugins
			// decide what to do in certain situations.  You can pass an array of specific features or
			// set it to FALSE to prevent any features from being added.  You can use
			// add_post_type_support() to add features or remove_post_type_support() to remove features
			// later.  The default features are 'title' and 'editor'.
			'supports' => array(
				// Post titles ($post->post_title).
				'title',
				// Post content ($post->post_content).
				'editor',
				/*// Post excerpt ($post->post_excerpt).
				'excerpt',*/
				/*// Post author ($post->post_author).
				'author',*/
				// Featured images (the user's theme must support 'post-thumbnails').
				'thumbnail',
				/*// Displays comments meta box.  If set, comments (any type) are allowed for the post.
				'comments',*/
				/*// Displays meta box to send trackbacks from the edit post screen.
				'trackbacks',*/
				// Displays the Custom Fields meta box. Post meta is supported regardless.
				'custom-fields',
				// Displays the Revisions meta box. If set, stores post revisions in the database.
				'revisions',
				// Displays the Attributes meta box with a parent selector and menu_order input box.
				'page-attributes',
				// Displays the Format meta box and allows post formats to be used with the posts.
				'post-formats',
			),
			// Labels used when displaying the posts in the admin and sometimes on the front end.  These
			// labels do not cover post updated, error, and related messages.  You'll need to filter the
			// 'post_updated_messages' hook to customize those.
			'labels' => array(
				'name'                  => __( 'Attorneys',                   'example-textdomain' ),
				'singular_name'         => __( 'Attorney',                    'example-textdomain' ),
				'menu_name'             => __( 'Attorneys',                   'example-textdomain' ),
				'name_admin_bar'        => __( 'Attorneys',                   'example-textdomain' ),
				'add_new'               => __( 'Add New',                 		'example-textdomain' ),
				'add_new_item'          => __( 'Add New Attorney',            'example-textdomain' ),
				'edit_item'             => __( 'Edit Attorney',               'example-textdomain' ),
				'new_item'              => __( 'New Attorney',                'example-textdomain' ),
				'view_item'             => __( 'View Attorney',               'example-textdomain' ),
				'search_items'          => __( 'Search Attorneys',            'example-textdomain' ),
				'not_found'             => __( 'No attorneys found',          'example-textdomain' ),
				'not_found_in_trash'    => __( 'No attorneys found in trash', 'example-textdomain' ),
				'all_items'             => __( 'All Attorneys',               'example-textdomain' ),
				'featured_image'        => __( 'Featured Image',          		'example-textdomain' ),
				'set_featured_image'    => __( 'Set featured image',      		'example-textdomain' ),
				'remove_featured_image' => __( 'Remove featured image',   		'example-textdomain' ),
				'use_featured_image'    => __( 'Use as featred image',    		'example-textdomain' ),
				'insert_into_item'      => __( 'Insert into attorney',        'example-textdomain' ),
				'uploaded_to_this_item' => __( 'Uploaded to this attorney',   'example-textdomain' ),
				'views'                 => __( 'Filter attorneys list',       'example-textdomain' ),
				'pagination'            => __( 'Attorneys list navigation',   'example-textdomain' ),
				'list'                  => __( 'Attorneys list',              'example-textdomain' ),
				// Labels for hierarchical post types only.
				'parent_item'        => __( 'Parent Attorney',                'example-textdomain' ),
				'parent_item_colon'  => __( 'Parent Attorney:',               'example-textdomain' ),
			)
		);
		// Register the post type.
		register_post_type(
			'attorney', // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
			$args      // Arguments for post type.
		);
	}

	function register_practice_area_cpt()
	{
// Set up the arguments for the post type.
		$args = array(
			// A short description of what your post type is. As far as I know, this isn't used anywhere
			// in core WordPress.  However, themes may choose to display this on post type archives.
			'description'         => __( 'Practice Areas', 'example-textdomain' ), // string
			// Whether the post type should be used publicly via the admin or by front-end users.  This
			// argument is sort of a catchall for many of the following arguments.  I would focus more
			// on adjusting them to your liking than this argument.
			'public'              => true, // bool (default is FALSE),
			// Shows it in the REST API
			'show_in_rest'			=> true,
			// Whether queries can be performed on the front end as part of parse_request().
			'publicly_queryable'  => true, // bool (defaults to 'public').
			// Whether to exclude posts with this post type from front end search results.
			'exclude_from_search' => false, // bool (defaults to the opposite of 'public' argument)
			// Whether individual post type items are available for selection in navigation menus.
			'show_in_nav_menus'   => true, // bool (defaults to 'public')
			// Whether to generate a default UI for managing this post type in the admin. You'll have
			// more control over what's shown in the admin with the other arguments.  To build your
			// own UI, set this to FALSE.
			'show_ui'             => true, // bool (defaults to 'public')
			// Whether to show post type in the admin menu. 'show_ui' must be true for this to work.
			// Can also set this to a string of a top-level menu (e.g., 'tools.php'), which will make
			// the post type screen be a sub-menu.
			'show_in_menu'        => true, // bool (defaults to 'show_ui')
			// Whether to make this post type available in the WordPress admin bar. The admin bar adds
			// a link to add a new post type item.
			'show_in_admin_bar'   => true, // bool (defaults to 'show_in_menu')
			// The position in the menu order the post type should appear. 'show_in_menu' must be true
			'menu_position'       => null, // int (defaults to 25 - below comments)
			// The URI to the icon to use for the admin menu item or a dashicon class. See:
			// https://developer.wordpress.org/resource/dashicons/
			'menu_icon'           => 'dashicons-portfolio', // string (defaults to use the post icon)
			// Whether the posts of this post type can be exported via the WordPress import/export plugin
			// or a similar plugin.
			'can_export'          => true, // bool (defaults to TRUE)
			// Whether to delete posts of this type when deleting a user who has written posts.
			'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')
			// Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts.
			'hierarchical'        => false, // bool (defaults to FALSE)
			// Whether the post type has an index/archive/root page like the "page for posts" for regular
			// posts. If set to TRUE, the post type name will be used for the archive slug.  You can also
			// set this to a string to control the exact name of the archive slug.
			'has_archive'         => false, // bool|string (defaults to FALSE)
			// Sets the query_var key for this post type. If set to TRUE, the post type name will be used.
			// You can also set this to a custom string to control the exact key.
			'query_var'           => true, // bool|string (defaults to TRUE - post type name)
			// A string used to build the edit, delete, and read capabilities for posts of this type. You
			// can use a string or an array (for singular and plural forms).  The array is useful if the
			// plural form can't be made by simply adding an 's' to the end of the word.  For example,
			// array( 'box', 'boxes' ).
			'capability_type'     => 'post', // string|array (defaults to 'post')
			// Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for
			// you.  If set to FALSE, you'll need to roll your own handling of this by filtering the
			// 'map_meta_cap' hook.
			'map_meta_cap'        => true, // bool (defaults to FALSE)
			// How the URL structure should be handled with this post type.  You can set this to an
			// array of specific arguments or true|false.  If set to FALSE, it will prevent rewrite
			// rules from being created.
			'rewrite' => array(
				// The slug to use for individual posts of this type.
				'slug'       => 'practice-areas', 'with_front' => false // string (defaults to the post type name)
				/*// Whether to show the $wp_rewrite->front slug in the permalink.
				'with_front' => false, // bool (defaults to TRUE)
				// Whether to allow single post pagination via the <!--nextpage--> quicktag.
				'pages'      => true, // bool (defaults to TRUE)
				// Whether to create pretty permalinks for feeds.
				'feeds'      => true, // bool (defaults to the 'has_archive' argument)
				// Assign an endpoint mask to this permalink.
				'ep_mask'    => EP_PERMALINK, // const (defaults to EP_PERMALINK)*/
			),
			'taxonomies' => array(
				'category','post_tag'
			),
			// What WordPress features the post type supports.  Many arguments are strictly useful on
			// the edit post screen in the admin.  However, this will help other themes and plugins
			// decide what to do in certain situations.  You can pass an array of specific features or
			// set it to FALSE to prevent any features from being added.  You can use
			// add_post_type_support() to add features or remove_post_type_support() to remove features
			// later.  The default features are 'title' and 'editor'.
			'supports' => array(
				// Post titles ($post->post_title).
				'title',
				// Post content ($post->post_content).
				'editor',
				/*// Post excerpt ($post->post_excerpt).
				'excerpt',*/
				/*// Post author ($post->post_author).
				'author',*/
				// Featured images (the user's theme must support 'post-thumbnails').
				'thumbnail',
				/*// Displays comments meta box.  If set, comments (any type) are allowed for the post.
				'comments',*/
				/*// Displays meta box to send trackbacks from the edit post screen.
				'trackbacks',*/
				// Displays the Custom Fields meta box. Post meta is supported regardless.
				/*'custom-fields',*/
				// Displays the Revisions meta box. If set, stores post revisions in the database.
				'revisions',
				// Displays the Attributes meta box with a parent selector and menu_order input box.
				'page-attributes',
				// Displays the Format meta box and allows post formats to be used with the posts.
				'post-formats',
			),
			// Labels used when displaying the posts in the admin and sometimes on the front end.  These
			// labels do not cover post updated, error, and related messages.  You'll need to filter the
			// 'post_updated_messages' hook to customize those.
			'labels' => array(
				'name'                  => __( 'Practice Areas',                   'example-textdomain' ),
				'singular_name'         => __( 'Practice Area',                    'example-textdomain' ),
				'menu_name'             => __( 'Practice Areas',                   'example-textdomain' ),
				'name_admin_bar'        => __( 'Practice Areas',                   'example-textdomain' ),
				'add_new'               => __( 'Add New',                 		'example-textdomain' ),
				'add_new_item'          => __( 'Add New Practice Area',            'example-textdomain' ),
				'edit_item'             => __( 'Edit Practice Area',               'example-textdomain' ),
				'new_item'              => __( 'New Practice Area',                'example-textdomain' ),
				'view_item'             => __( 'View Practice Area',               'example-textdomain' ),
				'search_items'          => __( 'Search Practice Areas',            'example-textdomain' ),
				'not_found'             => __( 'No practice areas found',          'example-textdomain' ),
				'not_found_in_trash'    => __( 'No practice areas found in trash', 'example-textdomain' ),
				'all_items'             => __( 'All Practice Areas',               'example-textdomain' ),
				'featured_image'        => __( 'Featured Image',          		'example-textdomain' ),
				'set_featured_image'    => __( 'Set featured image',      		'example-textdomain' ),
				'remove_featured_image' => __( 'Remove featured image',   		'example-textdomain' ),
				'use_featured_image'    => __( 'Use as featred image',    		'example-textdomain' ),
				'insert_into_item'      => __( 'Insert into practice area',        'example-textdomain' ),
				'uploaded_to_this_item' => __( 'Uploaded to this practice area',   'example-textdomain' ),
				'views'                 => __( 'Filter practice areas list',       'example-textdomain' ),
				'pagination'            => __( 'Practice Areas list navigation',   'example-textdomain' ),
				'list'                  => __( 'Practice Areas list',              'example-textdomain' ),
				// Labels for hierarchical post types only.
				'parent_item'        => __( 'Parent Practice Area',                'example-textdomain' ),
				'parent_item_colon'  => __( 'Parent Practice Area:',               'example-textdomain' ),
			)
		);
		// Register the post type.
		register_post_type(
			'practice-areas', // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
			$args      // Arguments for post type.
		);
	}

	function register_testimonial_cpt()
	{
		// Set up the arguments for the post type.
		$args = array(
			// A short description of what your post type is. As far as I know, this isn't used anywhere
			// in core WordPress.  However, themes may choose to display this on post type archives.
			'description'         => __( 'Testimonials', 'example-textdomain' ), // string
			// Whether the post type should be used publicly via the admin or by front-end users.  This
			// argument is sort of a catchall for many of the following arguments.  I would focus more
			// on adjusting them to your liking than this argument.
			'public'              => true, // bool (default is FALSE),
			// Shows it in the REST API
			'show_in_rest'			=> true,
			// Whether queries can be performed on the front end as part of parse_request().
			'publicly_queryable'  => true, // bool (defaults to 'public').
			// Whether to exclude posts with this post type from front end search results.
			'exclude_from_search' => false, // bool (defaults to the opposite of 'public' argument)
			// Whether individual post type items are available for selection in navigation menus.
			'show_in_nav_menus'   => true, // bool (defaults to 'public')
			// Whether to generate a default UI for managing this post type in the admin. You'll have
			// more control over what's shown in the admin with the other arguments.  To build your
			// own UI, set this to FALSE.
			'show_ui'             => true, // bool (defaults to 'public')
			// Whether to show post type in the admin menu. 'show_ui' must be true for this to work.
			// Can also set this to a string of a top-level menu (e.g., 'tools.php'), which will make
			// the post type screen be a sub-menu.
			'show_in_menu'        => true, // bool (defaults to 'show_ui')
			// Whether to make this post type available in the WordPress admin bar. The admin bar adds
			// a link to add a new post type item.
			'show_in_admin_bar'   => true, // bool (defaults to 'show_in_menu')
			// The position in the menu order the post type should appear. 'show_in_menu' must be true
			'menu_position'       => null, // int (defaults to 25 - below comments)
			// The URI to the icon to use for the admin menu item or a dashicon class. See:
			// https://developer.wordpress.org/resource/dashicons/
			'menu_icon'           => 'dashicons-editor-quote', // string (defaults to use the post icon)
			// Whether the posts of this post type can be exported via the WordPress import/export plugin
			// or a similar plugin.
			'can_export'          => true, // bool (defaults to TRUE)
			// Whether to delete posts of this type when deleting a user who has written posts.
			'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')
			// Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts.
			'hierarchical'        => false, // bool (defaults to FALSE)
			// Whether the post type has an index/archive/root page like the "page for posts" for regular
			// posts. If set to TRUE, the post type name will be used for the archive slug.  You can also
			// set this to a string to control the exact name of the archive slug.
			'has_archive'         => true, // bool|string (defaults to FALSE)
			// Sets the query_var key for this post type. If set to TRUE, the post type name will be used.
			// You can also set this to a custom string to control the exact key.
			'query_var'           => true, // bool|string (defaults to TRUE - post type name)
			// A string used to build the edit, delete, and read capabilities for posts of this type. You
			// can use a string or an array (for singular and plural forms).  The array is useful if the
			// plural form can't be made by simply adding an 's' to the end of the word.  For example,
			// array( 'box', 'boxes' ).
			'capability_type'     => 'post', // string|array (defaults to 'post')
			// Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for
			// you.  If set to FALSE, you'll need to roll your own handling of this by filtering the
			// 'map_meta_cap' hook.
			'map_meta_cap'        => true, // bool (defaults to FALSE)
			// How the URL structure should be handled with this post type.  You can set this to an
			// array of specific arguments or true|false.  If set to FALSE, it will prevent rewrite
			// rules from being created.
			'rewrite' => array(
				// The slug to use for individual posts of this type.
				'slug'       => 'testimonials', 'with_front' => false // string (defaults to the post type name)
				/*// Whether to show the $wp_rewrite->front slug in the permalink.
				'with_front' => false, // bool (defaults to TRUE)
				// Whether to allow single post pagination via the <!--nextpage--> quicktag.
				'pages'      => true, // bool (defaults to TRUE)
				// Whether to create pretty permalinks for feeds.
				'feeds'      => true, // bool (defaults to the 'has_archive' argument)
				// Assign an endpoint mask to this permalink.
				'ep_mask'    => EP_PERMALINK, // const (defaults to EP_PERMALINK)*/
			),
			'taxonomies' => array(
				'category','post_tag'
			),
			// What WordPress features the post type supports.  Many arguments are strictly useful on
			// the edit post screen in the admin.  However, this will help other themes and plugins
			// decide what to do in certain situations.  You can pass an array of specific features or
			// set it to FALSE to prevent any features from being added.  You can use
			// add_post_type_support() to add features or remove_post_type_support() to remove features
			// later.  The default features are 'title' and 'editor'.
			'supports' => array(
				// Post titles ($post->post_title).
				'title',
				// Post content ($post->post_content).
				'editor',
				// Post excerpt ($post->post_excerpt).
				'excerpt',
				/*// Post author ($post->post_author).
				'author',*/
				// Featured images (the user's theme must support 'post-thumbnails').
				'thumbnail',
				/*// Displays comments meta box.  If set, comments (any type) are allowed for the post.
				'comments',*/
				/*// Displays meta box to send trackbacks from the edit post screen.
				'trackbacks',*/
				// Displays the Custom Fields meta box. Post meta is supported regardless.
				/*'custom-fields',*/
				// Displays the Revisions meta box. If set, stores post revisions in the database.
				'revisions',
				// Displays the Attributes meta box with a parent selector and menu_order input box.
				'page-attributes',
				// Displays the Format meta box and allows post formats to be used with the posts.
				'post-formats',
			),
			// Labels used when displaying the posts in the admin and sometimes on the front end.  These
			// labels do not cover post updated, error, and related messages.  You'll need to filter the
			// 'post_updated_messages' hook to customize those.
			'labels' => array(
				'name'                  => __( 'Testimonials',                   'example-textdomain' ),
				'singular_name'         => __( 'Testimonial',                    'example-textdomain' ),
				'menu_name'             => __( 'Testimonials',                   'example-textdomain' ),
				'name_admin_bar'        => __( 'Testimonials',                   'example-textdomain' ),
				'add_new'               => __( 'Add New',                 		'example-textdomain' ),
				'add_new_item'          => __( 'Add New Testimonial',            'example-textdomain' ),
				'edit_item'             => __( 'Edit Testimonial',               'example-textdomain' ),
				'new_item'              => __( 'New Testimonial',                'example-textdomain' ),
				'view_item'             => __( 'View Testimonial',               'example-textdomain' ),
				'search_items'          => __( 'Search Testimonials',            'example-textdomain' ),
				'not_found'             => __( 'No testimonials found',          'example-textdomain' ),
				'not_found_in_trash'    => __( 'No testimonials found in trash', 'example-textdomain' ),
				'all_items'             => __( 'All Testimonials',               'example-textdomain' ),
				'featured_image'        => __( 'Featured Image',          		'example-textdomain' ),
				'set_featured_image'    => __( 'Set featured image',      		'example-textdomain' ),
				'remove_featured_image' => __( 'Remove featured image',   		'example-textdomain' ),
				'use_featured_image'    => __( 'Use as featred image',    		'example-textdomain' ),
				'insert_into_item'      => __( 'Insert into testimonial',        'example-textdomain' ),
				'uploaded_to_this_item' => __( 'Uploaded to this testimonial',   'example-textdomain' ),
				'views'                 => __( 'Filter testimonials list',       'example-textdomain' ),
				'pagination'            => __( 'Testimonials list navigation',   'example-textdomain' ),
				'list'                  => __( 'Testimonials list',              'example-textdomain' ),
				// Labels for hierarchical post types only.
				'parent_item'        => __( 'Parent Testimonial',                'example-textdomain' ),
				'parent_item_colon'  => __( 'Parent Testimonial:',               'example-textdomain' ),
			)
		);
		// Register the post type.
		register_post_type(
			'testimonials', // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
			$args      // Arguments for post type.
		);
	}

	function register_faq_cpt()
	{
		// Set up the arguments for the post type.
		$args = array(
			// A short description of what your post type is. As far as I know, this isn't used anywhere
			// in core WordPress.  However, themes may choose to display this on post type archives.
			'description'         => __( 'FAQs', 'example-textdomain' ), // string
			// Whether the post type should be used publicly via the admin or by front-end users.  This
			// argument is sort of a catchall for many of the following arguments.  I would focus more
			// on adjusting them to your liking than this argument.
			'public'              => true, // bool (default is FALSE),
			// Shows it in the REST API
			'show_in_rest'			=> true,
			// Whether queries can be performed on the front end as part of parse_request().
			'publicly_queryable'  => true, // bool (defaults to 'public').
			// Whether to exclude posts with this post type from front end search results.
			'exclude_from_search' => false, // bool (defaults to the opposite of 'public' argument)
			// Whether individual post type items are available for selection in navigation menus.
			'show_in_nav_menus'   => true, // bool (defaults to 'public')
			// Whether to generate a default UI for managing this post type in the admin. You'll have
			// more control over what's shown in the admin with the other arguments.  To build your
			// own UI, set this to FALSE.
			'show_ui'             => true, // bool (defaults to 'public')
			// Whether to show post type in the admin menu. 'show_ui' must be true for this to work.
			// Can also set this to a string of a top-level menu (e.g., 'tools.php'), which will make
			// the post type screen be a sub-menu.
			'show_in_menu'        => true, // bool (defaults to 'show_ui')
			// Whether to make this post type available in the WordPress admin bar. The admin bar adds
			// a link to add a new post type item.
			'show_in_admin_bar'   => true, // bool (defaults to 'show_in_menu')
			// The position in the menu order the post type should appear. 'show_in_menu' must be true
			'menu_position'       => null, // int (defaults to 25 - below comments)
			// The URI to the icon to use for the admin menu item or a dashicon class. See:
			// https://developer.wordpress.org/resource/dashicons/
			'menu_icon'           => 'dashicons-editor-help', // string (defaults to use the post icon)
			// Whether the posts of this post type can be exported via the WordPress import/export plugin
			// or a similar plugin.
			'can_export'          => true, // bool (defaults to TRUE)
			// Whether to delete posts of this type when deleting a user who has written posts.
			'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')
			// Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts.
			'hierarchical'        => false, // bool (defaults to FALSE)
			// Whether the post type has an index/archive/root page like the "page for posts" for regular
			// posts. If set to TRUE, the post type name will be used for the archive slug.  You can also
			// set this to a string to control the exact name of the archive slug.
			'has_archive'         => true, // bool|string (defaults to FALSE)
			// Sets the query_var key for this post type. If set to TRUE, the post type name will be used.
			// You can also set this to a custom string to control the exact key.
			'query_var'           => true, // bool|string (defaults to TRUE - post type name)
			// A string used to build the edit, delete, and read capabilities for posts of this type. You
			// can use a string or an array (for singular and plural forms).  The array is useful if the
			// plural form can't be made by simply adding an 's' to the end of the word.  For example,
			// array( 'box', 'boxes' ).
			'capability_type'     => 'post', // string|array (defaults to 'post')
			// Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for
			// you.  If set to FALSE, you'll need to roll your own handling of this by filtering the
			// 'map_meta_cap' hook.
			'map_meta_cap'        => true, // bool (defaults to FALSE)
			// How the URL structure should be handled with this post type.  You can set this to an
			// array of specific arguments or true|false.  If set to FALSE, it will prevent rewrite
			// rules from being created.
			'rewrite' => array(
				// The slug to use for individual posts of this type.
				'slug'       => 'faqs', 'with_front' => false // string (defaults to the post type name)
				/*// Whether to show the $wp_rewrite->front slug in the permalink.
				'with_front' => false, // bool (defaults to TRUE)
				// Whether to allow single post pagination via the <!--nextpage--> quicktag.
				'pages'      => true, // bool (defaults to TRUE)
				// Whether to create pretty permalinks for feeds.
				'feeds'      => true, // bool (defaults to the 'has_archive' argument)
				// Assign an endpoint mask to this permalink.
				'ep_mask'    => EP_PERMALINK, // const (defaults to EP_PERMALINK)*/
			),
			'taxonomies' => array(
				'category', 'post_tag'
			),
			// What WordPress features the post type supports.  Many arguments are strictly useful on
			// the edit post screen in the admin.  However, this will help other themes and plugins
			// decide what to do in certain situations.  You can pass an array of specific features or
			// set it to FALSE to prevent any features from being added.  You can use
			// add_post_type_support() to add features or remove_post_type_support() to remove features
			// later.  The default features are 'title' and 'editor'.
			'supports' => array(
				// Post titles ($post->post_title).
				'title',
				// Post content ($post->post_content).
				'editor',
				/*// Post excerpt ($post->post_excerpt).
				'excerpt',*/
				/*// Post author ($post->post_author).
				'author',*/
				// Featured images (the user's theme must support 'post-thumbnails').
				'thumbnail',
				/*// Displays comments meta box.  If set, comments (any type) are allowed for the post.
				'comments',*/
				/*// Displays meta box to send trackbacks from the edit post screen.
				'trackbacks',*/
				// Displays the Custom Fields meta box. Post meta is supported regardless.
				/*'custom-fields',*/
				// Displays the Revisions meta box. If set, stores post revisions in the database.
				'revisions',
				// Displays the Attributes meta box with a parent selector and menu_order input box.
				'page-attributes',
				// Displays the Format meta box and allows post formats to be used with the posts.
				'post-formats',
			),
			// Labels used when displaying the posts in the admin and sometimes on the front end.  These
			// labels do not cover post updated, error, and related messages.  You'll need to filter the
			// 'post_updated_messages' hook to customize those.
			'labels' => array(
				'name'                  => __( 'FAQs',                   'example-textdomain' ),
				'singular_name'         => __( 'FAQ',                    'example-textdomain' ),
				'menu_name'             => __( 'FAQs',                   'example-textdomain' ),
				'name_admin_bar'        => __( 'FAQs',                   'example-textdomain' ),
				'add_new'               => __( 'Add New',                 		'example-textdomain' ),
				'add_new_item'          => __( 'Add New FAQ',            'example-textdomain' ),
				'edit_item'             => __( 'Edit FAQ',               'example-textdomain' ),
				'new_item'              => __( 'New FAQ',                'example-textdomain' ),
				'view_item'             => __( 'View FAQ',               'example-textdomain' ),
				'search_items'          => __( 'Search FAQs',            'example-textdomain' ),
				'not_found'             => __( 'No faqs found',          'example-textdomain' ),
				'not_found_in_trash'    => __( 'No faqs found in trash', 'example-textdomain' ),
				'all_items'             => __( 'All FAQs',               'example-textdomain' ),
				'featured_image'        => __( 'Featured Image',          		'example-textdomain' ),
				'set_featured_image'    => __( 'Set featured image',      		'example-textdomain' ),
				'remove_featured_image' => __( 'Remove featured image',   		'example-textdomain' ),
				'use_featured_image'    => __( 'Use as featred image',    		'example-textdomain' ),
				'insert_into_item'      => __( 'Insert into FAQ',        'example-textdomain' ),
				'uploaded_to_this_item' => __( 'Uploaded to this FAQ',   'example-textdomain' ),
				'views'                 => __( 'Filter FAQs list',       'example-textdomain' ),
				'pagination'            => __( 'FAQs list navigation',   'example-textdomain' ),
				'list'                  => __( 'FAQs list',              'example-textdomain' ),
				// Labels for hierarchical post types only.
				'parent_item'        => __( 'Parent FAQ',                'example-textdomain' ),
				'parent_item_colon'  => __( 'Parent FAQ:',               'example-textdomain' ),
			)
		);
		// Register the post type.
		register_post_type(
			'faqs', // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
			$args      // Arguments for post type.
		);
	}

	function register_staff_cpt()
	{
		// Set up the arguments for the post type.
		$args = array(
			// A short description of what your post type is. As far as I know, this isn't used anywhere
			// in core WordPress.  However, themes may choose to display this on post type archives.
			'description'         => __( 'The Staff', 'example-textdomain' ), // string
			// Whether the post type should be used publicly via the admin or by front-end users.  This
			// argument is sort of a catchall for many of the following arguments.  I would focus more
			// on adjusting them to your liking than this argument.
			'public'              => true, // bool (default is FALSE),
			'rewrite'			  => ['with_front' => false],
			// Shows it in the REST API
			'show_in_rest'			=> true,
			// Whether queries can be performed on the front end as part of parse_request().
			'publicly_queryable'  => true, // bool (defaults to 'public').
			// Whether to exclude posts with this post type from front end search results.
			'exclude_from_search' => true, // bool (defaults to the opposite of 'public' argument)
			// Whether individual post type items are available for selection in navigation menus.
			'show_in_nav_menus'   => false, // bool (defaults to 'public')
			// Whether to generate a default UI for managing this post type in the admin. You'll have
			// more control over what's shown in the admin with the other arguments.  To build your
			// own UI, set this to FALSE.
			'show_ui'             => true, // bool (defaults to 'public')
			// Whether to show post type in the admin menu. 'show_ui' must be true for this to work.
			// Can also set this to a string of a top-level menu (e.g., 'tools.php'), which will make
			// the post type screen be a sub-menu.
			'show_in_menu'        => true, // bool (defaults to 'show_ui')
			// Whether to make this post type available in the WordPress admin bar. The admin bar adds
			// a link to add a new post type item.
			'show_in_admin_bar'   => true, // bool (defaults to 'show_in_menu')
			// The position in the menu order the post type should appear. 'show_in_menu' must be true
			'menu_position'       => null, // int (defaults to 25 - below comments)
			// The URI to the icon to use for the admin menu item or a dashicon class. See:
			// https://developer.wordpress.org/resource/dashicons/
			'menu_icon'           => 'dashicons-id', // string (defaults to use the post icon)
			// Whether the posts of this post type can be exported via the WordPress import/export plugin
			// or a similar plugin.
			'can_export'          => true, // bool (defaults to TRUE)
			// Whether to delete posts of this type when deleting a user who has written posts.
			'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')
			// Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts.
			'hierarchical'        => false, // bool (defaults to FALSE)
			// Whether the post type has an index/archive/root page like the "page for posts" for regular
			// posts. If set to TRUE, the post type name will be used for the archive slug.  You can also
			// set this to a string to control the exact name of the archive slug.
			'has_archive'         => true, // bool|string (defaults to FALSE)
			// Sets the query_var key for this post type. If set to TRUE, the post type name will be used.
			// You can also set this to a custom string to control the exact key.
			'query_var'           => true, // bool|string (defaults to TRUE - post type name)
			// A string used to build the edit, delete, and read capabilities for posts of this type. You
			// can use a string or an array (for singular and plural forms).  The array is useful if the
			// plural form can't be made by simply adding an 's' to the end of the word.  For example,
			// array( 'box', 'boxes' ).
			'capability_type'     => 'post', // string|array (defaults to 'post')
			// Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for
			// you.  If set to FALSE, you'll need to roll your own handling of this by filtering the
			// 'map_meta_cap' hook.
			'map_meta_cap'        => true, // bool (defaults to FALSE)
			// How the URL structure should be handled with this post type.  You can set this to an
			// array of specific arguments or true|false.  If set to FALSE, it will prevent rewrite
			// rules from being created.
			/*'taxonomies' => array(
				'category'
			),*/
			// What WordPress features the post type supports.  Many arguments are strictly useful on
			// the edit post screen in the admin.  However, this will help other themes and plugins
			// decide what to do in certain situations.  You can pass an array of specific features or
			// set it to FALSE to prevent any features from being added.  You can use
			// add_post_type_support() to add features or remove_post_type_support() to remove features
			// later.  The default features are 'title' and 'editor'.
			'supports' => array(
				// Post titles ($post->post_title).
				'title',
				// Post content ($post->post_content).
				'editor',
				/*// Post excerpt ($post->post_excerpt).
				'excerpt',*/
				/*// Post author ($post->post_author).
				'author',*/
				// Featured images (the user's theme must support 'post-thumbnails').
				'thumbnail',
				/*// Displays comments meta box.  If set, comments (any type) are allowed for the post.
				'comments',*/
				/*// Displays meta box to send trackbacks from the edit post screen.
				'trackbacks',*/
				// Displays the Custom Fields meta box. Post meta is supported regardless.
				/*'custom-fields',*/
				// Displays the Revisions meta box. If set, stores post revisions in the database.
				'revisions',
				// Displays the Attributes meta box with a parent selector and menu_order input box.
				'page-attributes',
				// Displays the Format meta box and allows post formats to be used with the posts.
				'post-formats',
			),
			// Labels used when displaying the posts in the admin and sometimes on the front end.  These
			// labels do not cover post updated, error, and related messages.  You'll need to filter the
			// 'post_updated_messages' hook to customize those.
			'labels' => array(
				'name'                  => __( 'Staff Members',                   'example-textdomain' ),
				'singular_name'         => __( 'Staff Member',                    'example-textdomain' ),
				'menu_name'             => __( 'Staff Members',                   'example-textdomain' ),
				'name_admin_bar'        => __( 'Staff Members',                   'example-textdomain' ),
				'add_new'               => __( 'Add New',                 		'example-textdomain' ),
				'add_new_item'          => __( 'Add New Staff Member',            'example-textdomain' ),
				'edit_item'             => __( 'Edit Staff Member',               'example-textdomain' ),
				'new_item'              => __( 'New Staff Member',                'example-textdomain' ),
				'view_item'             => __( 'View Staff Member',               'example-textdomain' ),
				'search_items'          => __( 'Search Staff Members',            'example-textdomain' ),
				'not_found'             => __( 'No staff members found',          'example-textdomain' ),
				'not_found_in_trash'    => __( 'No staff members found in trash', 'example-textdomain' ),
				'all_items'             => __( 'All Staff Members',               'example-textdomain' ),
				'featured_image'        => __( 'Featured Image',          		'example-textdomain' ),
				'set_featured_image'    => __( 'Set featured image',      		'example-textdomain' ),
				'remove_featured_image' => __( 'Remove featured image',   		'example-textdomain' ),
				'use_featured_image'    => __( 'Use as featred image',    		'example-textdomain' ),
				'insert_into_item'      => __( 'Insert into Staff Member',        'example-textdomain' ),
				'uploaded_to_this_item' => __( 'Uploaded to this Staff Member',   'example-textdomain' ),
				'views'                 => __( 'Filter Staff Members list',       'example-textdomain' ),
				'pagination'            => __( 'Staff Members list navigation',   'example-textdomain' ),
				'list'                  => __( 'Staff Members list',              'example-textdomain' ),
				// Labels for hierarchical post types only.
				'parent_item'        => __( 'Parent Staff Member',                'example-textdomain' ),
				'parent_item_colon'  => __( 'Parent Staff Member:',               'example-textdomain' ),
			)
		);
		// Register the post type.
		register_post_type(
			'staff', // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
			$args      // Arguments for post type.
		);
	}

	function register_case_result_cpt()
	{
		// Set up the arguments for the post type.
		$args = array(
			// A short description of what your post type is. As far as I know, this isn't used anywhere
			// in core WordPress.  However, themes may choose to display this on post type archives.
			'description'         => __( 'Case Results', 'example-textdomain' ), // string
			// Whether the post type should be used publicly via the admin or by front-end users.  This
			// argument is sort of a catchall for many of the following arguments.  I would focus more
			// on adjusting them to your liking than this argument.
			'public'              => true, // bool (default is FALSE),
			// Shows it in the REST API
			'show_in_rest'			=> true,
			// Whether queries can be performed on the front end as part of parse_request().
			'publicly_queryable'  => true, // bool (defaults to 'public').
			// Whether to exclude posts with this post type from front end search results.
			'exclude_from_search' => false, // bool (defaults to the opposite of 'public' argument)
			// Whether individual post type items are available for selection in navigation menus.
			'show_in_nav_menus'   => true, // bool (defaults to 'public')
			// Whether to generate a default UI for managing this post type in the admin. You'll have
			// more control over what's shown in the admin with the other arguments.  To build your
			// own UI, set this to FALSE.
			'show_ui'             => true, // bool (defaults to 'public')
			// Whether to show post type in the admin menu. 'show_ui' must be true for this to work.
			// Can also set this to a string of a top-level menu (e.g., 'tools.php'), which will make
			// the post type screen be a sub-menu.
			'show_in_menu'        => true, // bool (defaults to 'show_ui')
			// Whether to make this post type available in the WordPress admin bar. The admin bar adds
			// a link to add a new post type item.
			'show_in_admin_bar'   => true, // bool (defaults to 'show_in_menu')
			// The position in the menu order the post type should appear. 'show_in_menu' must be true
			'menu_position'       => null, // int (defaults to 25 - below comments)
			// The URI to the icon to use for the admin menu item or a dashicon class. See:
			// https://developer.wordpress.org/resource/dashicons/
			'menu_icon'           => 'dashicons-clipboard', // string (defaults to use the post icon)
			// Whether the posts of this post type can be exported via the WordPress import/export plugin
			// or a similar plugin.
			'can_export'          => true, // bool (defaults to TRUE)
			// Whether to delete posts of this type when deleting a user who has written posts.
			'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')
			// Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts.
			'hierarchical'        => false, // bool (defaults to FALSE)
			// Whether the post type has an index/archive/root page like the "page for posts" for regular
			// posts. If set to TRUE, the post type name will be used for the archive slug.  You can also
			// set this to a string to control the exact name of the archive slug.
			'has_archive'         => false, // bool|string (defaults to FALSE)
			// Sets the query_var key for this post type. If set to TRUE, the post type name will be used.
			// You can also set this to a custom string to control the exact key.
			'query_var'           => true, // bool|string (defaults to TRUE - post type name)
			// A string used to build the edit, delete, and read capabilities for posts of this type. You
			// can use a string or an array (for singular and plural forms).  The array is useful if the
			// plural form can't be made by simply adding an 's' to the end of the word.  For example,
			// array( 'box', 'boxes' ).
			'capability_type'     => 'post', // string|array (defaults to 'post')
			// Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for
			// you.  If set to FALSE, you'll need to roll your own handling of this by filtering the
			// 'map_meta_cap' hook.
			'map_meta_cap'        => true, // bool (defaults to FALSE)
			// How the URL structure should be handled with this post type.  You can set this to an
			// array of specific arguments or true|false.  If set to FALSE, it will prevent rewrite
			// rules from being created.
			'rewrite' => array(
				// The slug to use for individual posts of this type.
				'slug'       => 'injury-settlement', 'with_front' => false // string (defaults to the post type name)
				/*// Whether to show the $wp_rewrite->front slug in the permalink.
				'with_front' => false, // bool (defaults to TRUE)
				// Whether to allow single post pagination via the <!--nextpage--> quicktag.
				'pages'      => true, // bool (defaults to TRUE)
				// Whether to create pretty permalinks for feeds.
				'feeds'      => true, // bool (defaults to the 'has_archive' argument)
				// Assign an endpoint mask to this permalink.
				'ep_mask'    => EP_PERMALINK, // const (defaults to EP_PERMALINK)*/
			),
			'taxonomies' => array(
				'category', 'post_tag'
			),
			// What WordPress features the post type supports.  Many arguments are strictly useful on
			// the edit post screen in the admin.  However, this will help other themes and plugins
			// decide what to do in certain situations.  You can pass an array of specific features or
			// set it to FALSE to prevent any features from being added.  You can use
			// add_post_type_support() to add features or remove_post_type_support() to remove features
			// later.  The default features are 'title' and 'editor'.
			'supports' => array(
				// Post titles ($post->post_title).
				'title',
				// Post content ($post->post_content).
				'editor',
				/*// Post excerpt ($post->post_excerpt).
				'excerpt',*/
				/*// Post author ($post->post_author).
				'author',*/
				// Featured images (the user's theme must support 'post-thumbnails').
				'thumbnail',
				/*// Displays comments meta box.  If set, comments (any type) are allowed for the post.
				'comments',*/
				/*// Displays meta box to send trackbacks from the edit post screen.
				'trackbacks',*/
				// Displays the Custom Fields meta box. Post meta is supported regardless.
				/*'custom-fields',*/
				// Displays the Revisions meta box. If set, stores post revisions in the database.
				'revisions',
				// Displays the Attributes meta box with a parent selector and menu_order input box.
				'page-attributes',
				// Displays the Format meta box and allows post formats to be used with the posts.
				'post-formats',
			),
			// Labels used when displaying the posts in the admin and sometimes on the front end.  These
			// labels do not cover post updated, error, and related messages.  You'll need to filter the
			// 'post_updated_messages' hook to customize those.
			'labels' => array(
				'name'                  => __( 'Case Results',                   'example-textdomain' ),
				'singular_name'         => __( 'Case Result',                    'example-textdomain' ),
				'menu_name'             => __( 'Case Results',                   'example-textdomain' ),
				'name_admin_bar'        => __( 'Case Results',                   'example-textdomain' ),
				'add_new'               => __( 'Add New',                 		'example-textdomain' ),
				'add_new_item'          => __( 'Add New Case Result',            'example-textdomain' ),
				'edit_item'             => __( 'Edit Case Result',               'example-textdomain' ),
				'new_item'              => __( 'New Case Result',                'example-textdomain' ),
				'view_item'             => __( 'View Case Result',               'example-textdomain' ),
				'search_items'          => __( 'Search Case Results',            'example-textdomain' ),
				'not_found'             => __( 'No case results found',          'example-textdomain' ),
				'not_found_in_trash'    => __( 'No case results found in trash', 'example-textdomain' ),
				'all_items'             => __( 'All Case Results',               'example-textdomain' ),
				'featured_image'        => __( 'Featured Image',          		'example-textdomain' ),
				'set_featured_image'    => __( 'Set featured image',      		'example-textdomain' ),
				'remove_featured_image' => __( 'Remove featured image',   		'example-textdomain' ),
				'use_featured_image'    => __( 'Use as featred image',    		'example-textdomain' ),
				'insert_into_item'      => __( 'Insert into case result',        'example-textdomain' ),
				'uploaded_to_this_item' => __( 'Uploaded to this case result',   'example-textdomain' ),
				'views'                 => __( 'Filter case results list',       'example-textdomain' ),
				'pagination'            => __( 'Case Results list navigation',   'example-textdomain' ),
				'list'                  => __( 'Case Results list',              'example-textdomain' ),
				// Labels for hierarchical post types only.
				'parent_item'        => __( 'Parent Case Result',                'example-textdomain' ),
				'parent_item_colon'  => __( 'Parent Case Result:',               'example-textdomain' ),
			)
		);
		// Register the post type.
		register_post_type(
			'case-results', // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
			$args      // Arguments for post type.
		);
	}

	function register_content_cpt()
	{
		// Set up the arguments for the post type.
		$args = array(
			// A short description of what your post type is. As far as I know, this isn't used anywhere
			// in core WordPress.  However, themes may choose to display this on post type archives.
			'description'         => __( 'Content Snippets', 'example-textdomain' ), // string
			// Whether the post type should be used publicly via the admin or by front-end users.  This
			// argument is sort of a catchall for many of the following arguments.  I would focus more
			// on adjusting them to your liking than this argument.
			'public'              => true, // bool (default is FALSE),
			// Shows it in the REST API
			'show_in_rest'			=> true,
			// Whether queries can be performed on the front end as part of parse_request().
			'publicly_queryable'  => true, // bool (defaults to 'public').
			// Whether to exclude posts with this post type from front end search results.
			'exclude_from_search' => true, // bool (defaults to the opposite of 'public' argument)
			// Whether individual post type items are available for selection in navigation menus.
			'show_in_nav_menus'   => false, // bool (defaults to 'public')
			// Whether to generate a default UI for managing this post type in the admin. You'll have
			// more control over what's shown in the admin with the other arguments.  To build your
			// own UI, set this to FALSE.
			'show_ui'             => true, // bool (defaults to 'public')
			// Whether to show post type in the admin menu. 'show_ui' must be true for this to work.
			// Can also set this to a string of a top-level menu (e.g., 'tools.php'), which will make
			// the post type screen be a sub-menu.
			'show_in_menu'        => true, // bool (defaults to 'show_ui')
			// Whether to make this post type available in the WordPress admin bar. The admin bar adds
			// a link to add a new post type item.
			'show_in_admin_bar'   => true, // bool (defaults to 'show_in_menu')
			// The position in the menu order the post type should appear. 'show_in_menu' must be true
			'menu_position'       => null, // int (defaults to 25 - below comments)
			// The URI to the icon to use for the admin menu item or a dashicon class. See:
			// https://developer.wordpress.org/resource/dashicons/
			'menu_icon'           => 'dashicons-paperclip', // string (defaults to use the post icon)
			// Whether the posts of this post type can be exported via the WordPress import/export plugin
			// or a similar plugin.
			'can_export'          => true, // bool (defaults to TRUE)
			// Whether to delete posts of this type when deleting a user who has written posts.
			'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')
			// Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts.
			'hierarchical'        => false, // bool (defaults to FALSE)
			// Whether the post type has an index/archive/root page like the "page for posts" for regular
			// posts. If set to TRUE, the post type name will be used for the archive slug.  You can also
			// set this to a string to control the exact name of the archive slug.
			'has_archive'         => false, // bool|string (defaults to FALSE)
			// Sets the query_var key for this post type. If set to TRUE, the post type name will be used.
			// You can also set this to a custom string to control the exact key.
			'query_var'           => true, // bool|string (defaults to TRUE - post type name)
			// A string used to build the edit, delete, and read capabilities for posts of this type. You
			// can use a string or an array (for singular and plural forms).  The array is useful if the
			// plural form can't be made by simply adding an 's' to the end of the word.  For example,
			// array( 'box', 'boxes' ).
			'capability_type'     => 'post', // string|array (defaults to 'post')
			// Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for
			// you.  If set to FALSE, you'll need to roll your own handling of this by filtering the
			// 'map_meta_cap' hook.
			'map_meta_cap'        => true, // bool (defaults to FALSE)
			// How the URL structure should be handled with this post type.  You can set this to an
			// array of specific arguments or true|false.  If set to FALSE, it will prevent rewrite
			// rules from being created.
			'rewrite' => array(
				// The slug to use for individual posts of this type.
				//'slug'       => 'injury-settlement', 'with_front' => false // string (defaults to the post type name)
				/*// Whether to show the $wp_rewrite->front slug in the permalink.
				'with_front' => false, // bool (defaults to TRUE)
				// Whether to allow single post pagination via the <!--nextpage--> quicktag.
				'pages'      => true, // bool (defaults to TRUE)
				// Whether to create pretty permalinks for feeds.
				'feeds'      => true, // bool (defaults to the 'has_archive' argument)
				// Assign an endpoint mask to this permalink.
				'ep_mask'    => EP_PERMALINK, // const (defaults to EP_PERMALINK)*/
			),
			'taxonomies' => array(),
			// What WordPress features the post type supports.  Many arguments are strictly useful on
			// the edit post screen in the admin.  However, this will help other themes and plugins
			// decide what to do in certain situations.  You can pass an array of specific features or
			// set it to FALSE to prevent any features from being added.  You can use
			// add_post_type_support() to add features or remove_post_type_support() to remove features
			// later.  The default features are 'title' and 'editor'.
			'supports' => array(
				// Post titles ($post->post_title).
				'title',
				// Post content ($post->post_content).
				'editor',
				/*// Post excerpt ($post->post_excerpt).
				'excerpt',*/
				/*// Post author ($post->post_author).
				'author',*/
				// Featured images (the user's theme must support 'post-thumbnails').
				'thumbnail',
				/*// Displays comments meta box.  If set, comments (any type) are allowed for the post.
				'comments',*/
				/*// Displays meta box to send trackbacks from the edit post screen.
				'trackbacks',*/
				// Displays the Custom Fields meta box. Post meta is supported regardless.
				/*'custom-fields',*/
				// Displays the Revisions meta box. If set, stores post revisions in the database.
				'revisions',
				// Displays the Attributes meta box with a parent selector and menu_order input box.
				'page-attributes',
				// Displays the Format meta box and allows post formats to be used with the posts.
				'post-formats',
			),
			// Labels used when displaying the posts in the admin and sometimes on the front end.  These
			// labels do not cover post updated, error, and related messages.  You'll need to filter the
			// 'post_updated_messages' hook to customize those.
			'labels' => array(
				'name'                  => __( 'Content Snippets',                   'example-textdomain' ),
				'singular_name'         => __( 'Content Snippet',                    'example-textdomain' ),
				'menu_name'             => __( 'Content Snippets',                   'example-textdomain' ),
				'name_admin_bar'        => __( 'Content Snippets',                   'example-textdomain' ),
				'add_new'               => __( 'Add New',                 		'example-textdomain' ),
				'add_new_item'          => __( 'Add New Content Snippet',            'example-textdomain' ),
				'edit_item'             => __( 'Edit Content Snippet',               'example-textdomain' ),
				'new_item'              => __( 'New Content Snippet',                'example-textdomain' ),
				'view_item'             => __( 'View Content Snippet',               'example-textdomain' ),
				'search_items'          => __( 'Search Content Snippets',            'example-textdomain' ),
				'not_found'             => __( 'No snippets found',          'example-textdomain' ),
				'not_found_in_trash'    => __( 'No snippets found in trash', 'example-textdomain' ),
				'all_items'             => __( 'All Content Snippets',               'example-textdomain' ),
				'featured_image'        => __( 'Featured Image',          		'example-textdomain' ),
				'set_featured_image'    => __( 'Set featured image',      		'example-textdomain' ),
				'remove_featured_image' => __( 'Remove featured image',   		'example-textdomain' ),
				'use_featured_image'    => __( 'Use as featred image',    		'example-textdomain' ),
				'insert_into_item'      => __( 'Insert into case result',        'example-textdomain' ),
				'uploaded_to_this_item' => __( 'Uploaded to this case result',   'example-textdomain' ),
				'views'                 => __( 'Filter case results list',       'example-textdomain' ),
				'pagination'            => __( 'Content Snippets list navigation',   'example-textdomain' ),
				'list'                  => __( 'Content Snippets list',              'example-textdomain' ),
				// Labels for hierarchical post types only.
				'parent_item'        => __( 'Parent Content Snippet',                'example-textdomain' ),
				'parent_item_colon'  => __( 'Parent Content Snippet:',               'example-textdomain' ),
			)
		);
		// Register the post type.
		register_post_type(
			'content-snippet', // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
			$args      // Arguments for post type.
		);
	}

	function register_news_cpt()
	{
		// Set up the arguments for the post type.
		$args = array(
			// A short description of what your post type is. As far as I know, this isn't used anywhere
			// in core WordPress.  However, themes may choose to display this on post type archives.
			'description'         => __( 'News', 'example-textdomain' ), // string
			// Whether the post type should be used publicly via the admin or by front-end users.  This
			// argument is sort of a catchall for many of the following arguments.  I would focus more
			// on adjusting them to your liking than this argument.
			'public'              => true, // bool (default is FALSE),
			// Shows it in the REST API
			'show_in_rest'			=> true,
			// Whether queries can be performed on the front end as part of parse_request().
			'publicly_queryable'  => true, // bool (defaults to 'public').
			// Whether to exclude posts with this post type from front end search results.
			'exclude_from_search' => false, // bool (defaults to the opposite of 'public' argument)
			// Whether individual post type items are available for selection in navigation menus.
			'show_in_nav_menus'   => true, // bool (defaults to 'public')
			// Whether to generate a default UI for managing this post type in the admin. You'll have
			// more control over what's shown in the admin with the other arguments.  To build your
			// own UI, set this to FALSE.
			'show_ui'             => true, // bool (defaults to 'public')
			// Whether to show post type in the admin menu. 'show_ui' must be true for this to work.
			// Can also set this to a string of a top-level menu (e.g., 'tools.php'), which will make
			// the post type screen be a sub-menu.
			'show_in_menu'        => true, // bool (defaults to 'show_ui')
			// Whether to make this post type available in the WordPress admin bar. The admin bar adds
			// a link to add a new post type item.
			'show_in_admin_bar'   => true, // bool (defaults to 'show_in_menu')
			// The position in the menu order the post type should appear. 'show_in_menu' must be true
			'menu_position'       => null, // int (defaults to 25 - below comments)
			// The URI to the icon to use for the admin menu item or a dashicon class. See:
			// https://developer.wordpress.org/resource/dashicons/
			'menu_icon'           => 'dashicons-editor-table', // string (defaults to use the post icon)
			// Whether the posts of this post type can be exported via the WordPress import/export plugin
			// or a similar plugin.
			'can_export'          => true, // bool (defaults to TRUE)
			// Whether to delete posts of this type when deleting a user who has written posts.
			'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')
			// Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts.
			'hierarchical'        => false, // bool (defaults to FALSE)
			// Whether the post type has an index/archive/root page like the "page for posts" for regular
			// posts. If set to TRUE, the post type name will be used for the archive slug.  You can also
			// set this to a string to control the exact name of the archive slug.
			'has_archive'         => true, // bool|string (defaults to FALSE)
			// Sets the query_var key for this post type. If set to TRUE, the post type name will be used.
			// You can also set this to a custom string to control the exact key.
			'query_var'           => true, // bool|string (defaults to TRUE - post type name)
			// A string used to build the edit, delete, and read capabilities for posts of this type. You
			// can use a string or an array (for singular and plural forms).  The array is useful if the
			// plural form can't be made by simply adding an 's' to the end of the word.  For example,
			// array( 'box', 'boxes' ).
			'capability_type'     => 'post', // string|array (defaults to 'post')
			// Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for
			// you.  If set to FALSE, you'll need to roll your own handling of this by filtering the
			// 'map_meta_cap' hook.
			'map_meta_cap'        => true, // bool (defaults to FALSE)
			// How the URL structure should be handled with this post type.  You can set this to an
			// array of specific arguments or true|false.  If set to FALSE, it will prevent rewrite
			// rules from being created.
			'rewrite' => array(
				// The slug to use for individual posts of this type.
				'slug'       => 'news', 'with_front' => false // string (defaults to the post type name)
				/*// Whether to show the $wp_rewrite->front slug in the permalink.
				'with_front' => false, // bool (defaults to TRUE)
				// Whether to allow single post pagination via the <!--nextpage--> quicktag.
				'pages'      => true, // bool (defaults to TRUE)
				// Whether to create pretty permalinks for feeds.
				'feeds'      => true, // bool (defaults to the 'has_archive' argument)
				// Assign an endpoint mask to this permalink.
				'ep_mask'    => EP_PERMALINK, // const (defaults to EP_PERMALINK)*/
			),
			'taxonomies' => array(
				'category', 'post_tag'
			),
			// What WordPress features the post type supports.  Many arguments are strictly useful on
			// the edit post screen in the admin.  However, this will help other themes and plugins
			// decide what to do in certain situations.  You can pass an array of specific features or
			// set it to FALSE to prevent any features from being added.  You can use
			// add_post_type_support() to add features or remove_post_type_support() to remove features
			// later.  The default features are 'title' and 'editor'.
			'supports' => array(
				// Post titles ($post->post_title).
				'title',
				// Post content ($post->post_content).
				'editor',
				/*// Post excerpt ($post->post_excerpt).
				'excerpt',*/
				/*// Post author ($post->post_author).
				'author',*/
				// Featured images (the user's theme must support 'post-thumbnails').
				'thumbnail',
				/*// Displays comments meta box.  If set, comments (any type) are allowed for the post.
				'comments',*/
				/*// Displays meta box to send trackbacks from the edit post screen.
				'trackbacks',*/
				// Displays the Custom Fields meta box. Post meta is supported regardless.
				/*'custom-fields',*/
				// Displays the Revisions meta box. If set, stores post revisions in the database.
				'revisions',
				// Displays the Attributes meta box with a parent selector and menu_order input box.
				'page-attributes',
				// Displays the Format meta box and allows post formats to be used with the posts.
				'post-formats',
			),
			// Labels used when displaying the posts in the admin and sometimes on the front end.  These
			// labels do not cover post updated, error, and related messages.  You'll need to filter the
			// 'post_updated_messages' hook to customize those.
			'labels' => array(
				'name'                  => __( 'News Posts',                   'example-textdomain' ),
				'singular_name'         => __( 'News Post',                    'example-textdomain' ),
				'menu_name'             => __( 'News Posts',                   'example-textdomain' ),
				'name_admin_bar'        => __( 'News Posts',                   'example-textdomain' ),
				'add_new'               => __( 'Add New',                 		'example-textdomain' ),
				'add_new_item'          => __( 'Add News Post',            'example-textdomain' ),
				'edit_item'             => __( 'Edit News Post',               'example-textdomain' ),
				'new_item'              => __( 'New News Post',                'example-textdomain' ),
				'view_item'             => __( 'View News Post',               'example-textdomain' ),
				'search_items'          => __( 'Search News Posts',            'example-textdomain' ),
				'not_found'             => __( 'No news posts found',          'example-textdomain' ),
				'not_found_in_trash'    => __( 'No news posts found in trash', 'example-textdomain' ),
				'all_items'             => __( 'All News Posts',               'example-textdomain' ),
				'featured_image'        => __( 'Featured Image',          		'example-textdomain' ),
				'set_featured_image'    => __( 'Set featured image',      		'example-textdomain' ),
				'remove_featured_image' => __( 'Remove featured image',   		'example-textdomain' ),
				'use_featured_image'    => __( 'Use as featred image',    		'example-textdomain' ),
				'insert_into_item'      => __( 'Insert into news post',        'example-textdomain' ),
				'uploaded_to_this_item' => __( 'Uploaded to this news post',   'example-textdomain' ),
				'views'                 => __( 'Filter news posts list',       'example-textdomain' ),
				'pagination'            => __( 'News Posts list navigation',   'example-textdomain' ),
				'list'                  => __( 'News Posts list',              'example-textdomain' ),
				// Labels for hierarchical post types only.
				'parent_item'        => __( 'Parent News Post',                'example-textdomain' ),
				'parent_item_colon'  => __( 'Parent News Post:',               'example-textdomain' ),
			)
		);
		// Register the post type.
		register_post_type(
			'news', // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
			$args      // Arguments for post type.
		);
	}

	function register_video_cpt()
	{
		// Set up the arguments for the post type.
		$args = array(
			// A short description of what your post type is. As far as I know, this isn't used anywhere
			// in core WordPress.  However, themes may choose to display this on post type archives.
			'description'         => __( 'Videos', 'example-textdomain' ), // string
			// Whether the post type should be used publicly via the admin or by front-end users.  This
			// argument is sort of a catchall for many of the following arguments.  I would focus more
			// on adjusting them to your liking than this argument.
			'public'              => true, // bool (default is FALSE),
			// Shows it in the REST API
			'show_in_rest'			=> true,
			// Whether queries can be performed on the front end as part of parse_request().
			'publicly_queryable'  => true, // bool (defaults to 'public').
			// Whether to exclude posts with this post type from front end search results.
			'exclude_from_search' => false, // bool (defaults to the opposite of 'public' argument)
			// Whether individual post type items are available for selection in navigation menus.
			'show_in_nav_menus'   => true, // bool (defaults to 'public')
			// Whether to generate a default UI for managing this post type in the admin. You'll have
			// more control over what's shown in the admin with the other arguments.  To build your
			// own UI, set this to FALSE.
			'show_ui'             => true, // bool (defaults to 'public')
			// Whether to show post type in the admin menu. 'show_ui' must be true for this to work.
			// Can also set this to a string of a top-level menu (e.g., 'tools.php'), which will make
			// the post type screen be a sub-menu.
			'show_in_menu'        => true, // bool (defaults to 'show_ui')
			// Whether to make this post type available in the WordPress admin bar. The admin bar adds
			// a link to add a new post type item.
			'show_in_admin_bar'   => true, // bool (defaults to 'show_in_menu')
			// The position in the menu order the post type should appear. 'show_in_menu' must be true
			'menu_position'       => null, // int (defaults to 25 - below comments)
			// The URI to the icon to use for the admin menu item or a dashicon class. See:
			// https://developer.wordpress.org/resource/dashicons/
			'menu_icon'           => 'dashicons-video-alt2', // string (defaults to use the post icon)
			// Whether the posts of this post type can be exported via the WordPress import/export plugin
			// or a similar plugin.
			'can_export'          => true, // bool (defaults to TRUE)
			// Whether to delete posts of this type when deleting a user who has written posts.
			'delete_with_user'    => false, // bool (defaults to TRUE if the post type supports 'author')
			// Whether this post type should allow hierarchical (parent/child/grandchild/etc.) posts.
			'hierarchical'        => false, // bool (defaults to FALSE)
			// Whether the post type has an index/archive/root page like the "page for posts" for regular
			// posts. If set to TRUE, the post type name will be used for the archive slug.  You can also
			// set this to a string to control the exact name of the archive slug.
			'has_archive'         => true, // bool|string (defaults to FALSE)
			// Sets the query_var key for this post type. If set to TRUE, the post type name will be used.
			// You can also set this to a custom string to control the exact key.
			'query_var'           => true, // bool|string (defaults to TRUE - post type name)
			// A string used to build the edit, delete, and read capabilities for posts of this type. You
			// can use a string or an array (for singular and plural forms).  The array is useful if the
			// plural form can't be made by simply adding an 's' to the end of the word.  For example,
			// array( 'box', 'boxes' ).
			'capability_type'     => 'post', // string|array (defaults to 'post')
			// Whether WordPress should map the meta capabilities (edit_post, read_post, delete_post) for
			// you.  If set to FALSE, you'll need to roll your own handling of this by filtering the
			// 'map_meta_cap' hook.
			'map_meta_cap'        => true, // bool (defaults to FALSE)
			// How the URL structure should be handled with this post type.  You can set this to an
			// array of specific arguments or true|false.  If set to FALSE, it will prevent rewrite
			// rules from being created.
			'rewrite' => array(
				// The slug to use for individual posts of this type.
				'slug'       => 'video', 'with_front' => false // string (defaults to the post type name)
				/*// Whether to show the $wp_rewrite->front slug in the permalink.
				'with_front' => false, // bool (defaults to TRUE)
				// Whether to allow single post pagination via the <!--nextpage--> quicktag.
				'pages'      => true, // bool (defaults to TRUE)
				// Whether to create pretty permalinks for feeds.
				'feeds'      => true, // bool (defaults to the 'has_archive' argument)
				// Assign an endpoint mask to this permalink.
				'ep_mask'    => EP_PERMALINK, // const (defaults to EP_PERMALINK)*/
			),
			'taxonomies' => array(
				'category', 'post_tag'
			),
			// What WordPress features the post type supports.  Many arguments are strictly useful on
			// the edit post screen in the admin.  However, this will help other themes and plugins
			// decide what to do in certain situations.  You can pass an array of specific features or
			// set it to FALSE to prevent any features from being added.  You can use
			// add_post_type_support() to add features or remove_post_type_support() to remove features
			// later.  The default features are 'title' and 'editor'.
			'supports' => array(
				// Post titles ($post->post_title).
				'title',
				// Post content ($post->post_content).
				'editor',
				/*// Post excerpt ($post->post_excerpt).
				'excerpt',*/
				/*// Post author ($post->post_author).
				'author',*/
				// Featured images (the user's theme must support 'post-thumbnails').
				'thumbnail',
				/*// Displays comments meta box.  If set, comments (any type) are allowed for the post.
				'comments',*/
				/*// Displays meta box to send trackbacks from the edit post screen.
				'trackbacks',*/
				// Displays the Custom Fields meta box. Post meta is supported regardless.
				/*'custom-fields',*/
				// Displays the Revisions meta box. If set, stores post revisions in the database.
				'revisions',
				// Displays the Attributes meta box with a parent selector and menu_order input box.
				'page-attributes',
				// Displays the Format meta box and allows post formats to be used with the posts.
				'post-formats',
			),
			// Labels used when displaying the posts in the admin and sometimes on the front end.  These
			// labels do not cover post updated, error, and related messages.  You'll need to filter the
			// 'post_updated_messages' hook to customize those.
			'labels' => array(
				'name'                  => __( 'Videos',                   'example-textdomain' ),
				'singular_name'         => __( 'Video',                    'example-textdomain' ),
				'menu_name'             => __( 'Videos',                   'example-textdomain' ),
				'name_admin_bar'        => __( 'Videos',                   'example-textdomain' ),
				'add_new'               => __( 'Add New',                 		'example-textdomain' ),
				'add_new_item'          => __( 'Add New Video',            'example-textdomain' ),
				'edit_item'             => __( 'Edit Video',               'example-textdomain' ),
				'new_item'              => __( 'New Video',                'example-textdomain' ),
				'view_item'             => __( 'View Video',               'example-textdomain' ),
				'search_items'          => __( 'Search Videos',            'example-textdomain' ),
				'not_found'             => __( 'No videos found',          'example-textdomain' ),
				'not_found_in_trash'    => __( 'No videos found in trash', 'example-textdomain' ),
				'all_items'             => __( 'All Videos',               'example-textdomain' ),
				'featured_image'        => __( 'Featured Image',          		'example-textdomain' ),
				'set_featured_image'    => __( 'Set featured image',      		'example-textdomain' ),
				'remove_featured_image' => __( 'Remove featured image',   		'example-textdomain' ),
				'use_featured_image'    => __( 'Use as featred image',    		'example-textdomain' ),
				'insert_into_item'      => __( 'Insert into Video',        'example-textdomain' ),
				'uploaded_to_this_item' => __( 'Uploaded to this Video',   'example-textdomain' ),
				'views'                 => __( 'Filter Videos list',       'example-textdomain' ),
				'pagination'            => __( 'Videos list navigation',   'example-textdomain' ),
				'list'                  => __( 'Videos list',              'example-textdomain' ),
				// Labels for hierarchical post types only.
				'parent_item'        => __( 'Parent Video',                'example-textdomain' ),
				'parent_item_colon'  => __( 'Parent Video:',               'example-textdomain' ),
			)
		);
		// Register the post type.
		register_post_type(
			'video', // Post type name. Max of 20 characters. Uppercase and spaces not allowed.
			$args      // Arguments for post type.
		);
	}

	function register_legacy_cpt()
	{
		# LEGACY POST TYPE, LIBRARY
//		register_post_type( 'library',
//			array(
//				'labels' => array(
//					'name' => __( 'Library' ),
//					'singular_name' => __( 'Library' )
//				),
//				'public' => true,
//				'menu_icon' => 'dashicons-book', // string (defaults to use the post icon)
//				'has_archive' => true,
//				'rewrite' => array('slug' => 'library'),
//				'taxonomies' => array('category'),
//				'supports' => array(
//					// Post titles ($post->post_title).
//					'title',
//					// Post content ($post->post_content).
//					'editor',
//					/*// Post excerpt ($post->post_excerpt).
//                    'excerpt',*/
//					/*// Post author ($post->post_author).
//                    'author',*/
//					// Featured images (the user's theme must support 'post-thumbnails').
//					'thumbnail',
//					/*// Displays comments meta box.  If set, comments (any type) are allowed for the post.
//                    'comments',*/
//					/*// Displays meta box to send trackbacks from the edit post screen.
//                    'trackbacks',*/
//					// Displays the Custom Fields meta box. Post meta is supported regardless.
//					/*'custom-fields',*/
//					// Displays the Revisions meta box. If set, stores post revisions in the database.
//					'revisions',
//					// Displays the Attributes meta box with a parent selector and menu_order input box.
//					'page-attributes',
//					// Displays the Format meta box and allows post formats to be used with the posts.
//					'post-formats',
//				)
//			)
//		);

		# LEGACY POST TYPE, REPORTS
//		register_post_type( 'reports',
//			array(
//				'labels' => array(
//					'name' => __( 'Reports' ),
//					'singular_name' => __( 'Reports' )
//				),
//				'public' => true,
//				'menu_icon' => 'dashicons-media-text', // string (defaults to use the post icon)
//				'has_archive' => true,
//				'rewrite' => array('slug' => 'reports'),
//				'taxonomies' => array('category'),
//				'supports' => array(
//					// Post titles ($post->post_title).
//					'title',
//					// Post content ($post->post_content).
//					'editor',
//					/*// Post excerpt ($post->post_excerpt).
//                    'excerpt',*/
//					/*// Post author ($post->post_author).
//                    'author',*/
//					// Featured images (the user's theme must support 'post-thumbnails').
//					'thumbnail',
//					/*// Displays comments meta box.  If set, comments (any type) are allowed for the post.
//                    'comments',*/
//					/*// Displays meta box to send trackbacks from the edit post screen.
//                    'trackbacks',*/
//					// Displays the Custom Fields meta box. Post meta is supported regardless.
//					/*'custom-fields',*/
//					// Displays the Revisions meta box. If set, stores post revisions in the database.
//					'revisions',
//					// Displays the Attributes meta box with a parent selector and menu_order input box.
//					'page-attributes',
//					// Displays the Format meta box and allows post formats to be used with the posts.
//					'post-formats',
//				)
//			)
//		);

		# LEGACY POST TYPE, SIGN-UP
//		register_post_type( 'sign-up',
//			array(
//				'labels' => array(
//					'name' => __( 'Sign-Up' ),
//					'singular_name' => __( 'Sign-Up' )
//				),
//				'public' => true,
//				'menu_icon' => 'dashicons-welcome-widgets-menus', // string (defaults to use the post icon)
//				'has_archive' => true,
//				'rewrite' => array('slug' => 'sign-up'),
//				'taxonomies' => array('category'),
//				'supports' => array(
//					// Post titles ($post->post_title).
//					'title',
//					// Post content ($post->post_content).
//					'editor',
//					/*// Post excerpt ($post->post_excerpt).
//                    'excerpt',*/
//					/*// Post author ($post->post_author).
//                    'author',*/
//					// Featured images (the user's theme must support 'post-thumbnails').
//					'thumbnail',
//					/*// Displays comments meta box.  If set, comments (any type) are allowed for the post.
//                    'comments',*/
//					/*// Displays meta box to send trackbacks from the edit post screen.
//                    'trackbacks',*/
//					// Displays the Custom Fields meta box. Post meta is supported regardless.
//					/*'custom-fields',*/
//					// Displays the Revisions meta box. If set, stores post revisions in the database.
//					'revisions',
//					// Displays the Attributes meta box with a parent selector and menu_order input box.
//					'page-attributes',
//					// Displays the Format meta box and allows post formats to be used with the posts.
//					'post-formats',
//				)
//			)
//		);
	}

	/**
	 * Taxonomy Methods
	 */
	function register_faq_type_tax()
	{
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'FAQ Types', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'FAQ Type', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search FAQ Types', 'textdomain' ),
			'all_items'         => __( 'All FAQ Types', 'textdomain' ),
			'parent_item'       => __( 'Parent FAQ Type', 'textdomain' ),
			'parent_item_colon' => __( 'Parent FAQ Type:', 'textdomain' ),
			'edit_item'         => __( 'Edit FAQ Type', 'textdomain' ),
			'update_item'       => __( 'Update FAQ Type', 'textdomain' ),
			'add_new_item'      => __( 'Add New FAQ Type', 'textdomain' ),
			'new_item_name'     => __( 'New FAQ Type Name', 'textdomain' ),
			'menu_name'         => __( 'FAQ Type', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'faq-type', 'with_front' => false ),
		);

		register_taxonomy( 'faq-type', array( 'faqs' ), $args );

	}

	function register_case_result_tag_tax()
	{
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Case Result Tags', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Case Result Tag', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Case Result Tags', 'textdomain' ),
			'all_items'         => __( 'All Case Result Tags', 'textdomain' ),
			'parent_item'       => __( 'Parent Case Result Tag', 'textdomain' ),
			'parent_item_colon' => __( 'Parent Case Result Tag:', 'textdomain' ),
			'edit_item'         => __( 'Edit Case Result Tag', 'textdomain' ),
			'update_item'       => __( 'Update Case Result Tag', 'textdomain' ),
			'add_new_item'      => __( 'Add New Case Result Tag', 'textdomain' ),
			'new_item_name'     => __( 'New Case Result Tag Name', 'textdomain' ),
			'menu_name'         => __( 'Case Result Tag', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'case-result-tag', 'with_front' => false ),
		);

		register_taxonomy( 'case-result-tag', array( 'case-results' ), $args );

	}

	function register_case_result_claim_type_tax()
	{
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Claim Types', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Claim Type', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Claim Types', 'textdomain' ),
			'all_items'         => __( 'All Claim Types', 'textdomain' ),
			'parent_item'       => __( 'Parent Claim Type', 'textdomain' ),
			'parent_item_colon' => __( 'Parent Claim Type:', 'textdomain' ),
			'edit_item'         => __( 'Edit Claim Type', 'textdomain' ),
			'update_item'       => __( 'Update Claim Type', 'textdomain' ),
			'add_new_item'      => __( 'Add New Claim Type', 'textdomain' ),
			'new_item_name'     => __( 'New Claim Type Name', 'textdomain' ),
			'menu_name'         => __( 'Claim Types', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'claim-type', 'with_front' => false ),
		);

		register_taxonomy( 'claim-type', array( 'case-results' ), $args );

	}

	function register_video_category_tax()
	{
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Video Categories', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Video Category', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Video Categories', 'textdomain' ),
			'all_items'         => __( 'All Video Categories', 'textdomain' ),
			'parent_item'       => __( 'Parent Video Category', 'textdomain' ),
			'parent_item_colon' => __( 'Parent Video Category:', 'textdomain' ),
			'edit_item'         => __( 'Edit Video Category', 'textdomain' ),
			'update_item'       => __( 'Update Video Category', 'textdomain' ),
			'add_new_item'      => __( 'Add New Video Category', 'textdomain' ),
			'new_item_name'     => __( 'New Video Category Name', 'textdomain' ),
			'menu_name'         => __( 'Video Categories', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'video-category', 'with_front' => false ),
		);

		register_taxonomy( 'video-category', array( 'video' ), $args );

	}

	/**
	 * @param $context
	 * @return mixed
	 *
	 * These are the variables that'll be available in ANY Timber context.
	 * For me, this is probably a good place to add the CSS/JS directories.
	 *
	 * Also, once we're ready, all CSS and JS should be in their own files, and
	 * we should probably pre-minify them (somehow)
	 *
	 */
	function add_to_context( $context ) {

		/*
		$context['foo'] = 'bar';
		$context['stuff'] = 'I am a value set in your functions.php file';
		$context['notes'] = 'These values are available everytime you call Timber::get_context();';
		$context['menu'] = new TimberMenu();
		$context['site'] = $this;
		*/

		# Link to the home URI
		$context['home_link'] = get_bloginfo('wpurl');

		# Developer stuff
		$context['ajaxurl'] = admin_url('admin-ajax.php');

		# Menu context for the main header
		$context['main_header_menu'] = new TimberMenu('main_header_menu');
		$context['main_mobile_menu'] = new TimberMenu('main_mobile_menu');
		$context['homepage_menu'] = new TimberMenu('homepage_menu');

		# Resource directories
		$context['css_dir'] = get_stylesheet_directory_uri() . "/res/css";
		$context['js_dir'] = get_stylesheet_directory_uri() . "/res/js";
		$context['img_dir'] = get_stylesheet_directory_uri() . "/img";

		# Theme options (global, like colors, etc.)
		$context['primary_phone'] = ot_get_option('g_primary_phone_number');
		$context['cta_header_title'] = ot_get_option('ho_call_to_action_title');
		$context['primary_logo'] = ot_get_option('g_primary_company_logo');
		$context['primary_fax'] = ot_get_option('g_primary_fax_number');
		$context['addr_line_1'] = ot_get_option('g_company_address_line_1');
		$context['addr_line_2'] = ot_get_option('g_company_address_line_2');
		$context['map_embed_code'] = ot_get_option('g_company_map_embed_code');
		$context['seo_image_alt_tag'] = ot_get_option('jal_main_logo_image_alt_tag');

		# Theme colors
		$context['colors']['background'] = ot_get_option('colors_site_background', 'white');
		$context['colors']['body_font_color'] = ot_get_option('colors_body_font_color', 'black');
		$context['colors']['links'] = ot_get_option('colors_links', 'blue');
		$context['colors']['visited_links'] = ot_get_option('colors_links_visited', 'blue');
		$context['colors']['primary'] = ot_get_option('colors_primary_background_color', '#27597c');
		$context['colors']['footer_primary'] = ot_get_option('colors_footer_color_primary', '#4a4a4a');
		$context['colors']['footer_secondary'] = ot_get_option('colors_footer_color_secondary', '#262626');

		# Social theme options
		$context['facebook_url'] = ot_get_option('ss_facebook_url');
		$context['twitter_url'] = ot_get_option('ss_twitter_url');
		$context['linkedin_url'] = ot_get_option('ss_linkedin_url');
		$context['google_url'] = ot_get_option('ss_google_url');
		$context['youtube_url'] = ot_get_option('ss_youtube_url');

		# Any globally used sidebars
		$context['footer_sidebar_logos'] = Timber::get_widgets('footer-sidebar-logos');
		$context['mega_menu_sidebar'] = Timber::get_widgets('mega-menu-sidebar');
		$context['footer_sidebar_a'] = Timber::get_widgets('footer-sidebar-a');
		$context['footer_sidebar_b'] = Timber::get_widgets('footer-sidebar-b');
		$context['footer_sidebar_c'] = Timber::get_widgets('footer-sidebar-c');
		$context['primary_sidebar'] = Timber::get_widgets('primary-sidebar');
		$context['secondary_sidebar'] = Timber::get_widgets('secondary-sidebar');


		return $context;
	}

	/**
	 * @param $twig
	 * @return Twig_Environment
	 *
	 * This is actually a sample Twig filter, so that's cool we'll keep this for now.
	 */
	/*
	function myfoo( $text ) {
		$text .= ' bar!';
		return $text;
	}
	*/

	/**
	 * @param $text
	 * @return string
	 */
	function get_youtube_id( $text )
	{
		$youtube_id = '-uK-Zf5IucE';
		if($text){
			$url = $text;
		}
		preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
		if($match[1]){
			$youtube_id = $match[1];
		}
		return $youtube_id;
	}

	/**
	 * @param $twig
	 * @return Twig_Environment
	 *
	 * This is probably where we'll actually set up all the Twig stuff, so let's keep most of these examples.
	 */
	function add_to_twig( $twig ) {
		/** @var Twig_Environment $twig */

		/* this is where you can add your own functions to twig */
		$twig->addExtension( new Twig_Extension_StringLoader() );

		/**
		 * Adds a filter for grabbing a YouTube IDs
		 */
		$twig->addFilter('youtube_id', new Twig_SimpleFilter('youtube_id', array($this, 'get_youtube_id')));

		# Debugging
		$twig->addExtension( new Twig_Extension_Debug() );

		# Debugging
		$twig->enableDebug();

		return $twig;
	}

	/**
	 * @param $args
	 *
	 * This will render the final results for the Chained Quiz -- this is also where we can likely integrate
	 * the InfusionSoft integration outward.
	 */
	function register_quiz_results_shortcode($args)
	{

		$points = $args[0];

		$context = Timber::get_context();

		$context['points'] = $points;

		Timber::render( array( 'calculator-results.twig' ), $context );

	}

	/**
	 * @param $args
	 *
	 * This will render the FAQ Tabbed widget, along with any content for each of the tabs that
	 * we'll need on the FAQ -- We should use the FAQ code here from the archive to produce a similar result.
	 */
	function register_faq_widget_shortcode($args)
	{
		$context = Timber::get_context();

		# the types of taxes here
		$terms = get_terms( array(
			'taxonomy' => 'faq-type',
			'hide_empty' => false,
		) );
		$context['terms'] = $terms;

	# if we have taxes, lets go through each and find their posts
		$term_posts = [];
		foreach($terms as $term)
		{
			if($term->count > 0){
				$term_posts[$term->slug] =  Timber::get_posts([
					'post_type' => 'faqs',
					'tax_query' => array(
						array(
							'taxonomy' => 'faq-type',
							'field'    => 'slug',
							'terms'    => $term->slug,
						),
					),
				]);
			}
		}
		$context['posts'] = $term_posts;

		Timber::render( array( 'faq-tabs.twig' ), $context );
	}

	/**
	 * @param $args
	 *
	 * This will render the Claim Calculator widget, which will pull answers from our WordPress back-end to
	 * populate the widget (This replaces the previous 'Chained Quiz' widget)
	 */
	function register_quiz_widget_shortcode($args)
	{

		/*
		 * Build the Timber context for twig
		 */
		$context = Timber::get_context();

		/*
		 * Get the ID of the Chained Quiz
		 */
		$chained_quiz_id = $args[0];

		/** @var wpdb $wpdb */
		global $wpdb;

		/*
		 * Here, we want to load all the Chained Quiz information we need to build out the calculator.
		 *
		 */
		$query = "SELECT choice,points,title,question,question_id,cc.id AS choice_id FROM wp_chained_choices cc INNER JOIN wp_chained_questions cq ON cq.id = cc.question_id WHERE cc.quiz_id = " . $chained_quiz_id . " ORDER BY cq.sort_order ASC, cc.id ASC;";
		$all_answers = $wpdb->get_results($query);

		/*
		 * Now, we loop through all the questions, group them together, etc.
		 */
		$steps = [];
		foreach($all_answers as $answer)
		{
			$steps[$answer->question_id]['question_id'] = $answer->question_id;
			$steps[$answer->question_id]['question_text'] = stripcslashes($answer->question);
			$steps[$answer->question_id]['question_title'] = stripcslashes($answer->title);
			$steps[$answer->question_id]['answers'][] = [
				'choice' => stripcslashes($answer->choice),
				'points' => intval($answer->points),
				'answer_id' => $answer->choice_id
			];
		}
		$context['steps'] = array_values($steps);
		$context['quiz_id'] = $chained_quiz_id;
		$context['total_steps'] = count($steps);

		Timber::render( array( 'widgets/claim-calculator.twig' ), $context );

	}

	/**
	 * This function will create the appropriate query, and generate what the HTML would look like, then pass it back
	 * to the browser
	 */
	function infinite_scroller_ajax()
	{
		/*
		 * Build the Timber context for twig
		 */
		$context = Timber::get_context();

		$post_type = $_POST['post_type'];
		$category = $_POST['category'];
		$page = ( isset($_POST['page']) ) ? $_POST['page'] : 1;

		if( $post_type )
		{
			$args = [
				'post_type' => $post_type,
				'paged' => $page,
				'post_status' => 'publish'
			];

			if( $category && $category != "" )
			{
				$args['tax_query'] = [
					'relation' => 'AND',
					[
						'taxonomy' => 'category',
						'field'    => 'slug',
						'terms'    => [$category],
						'operator' => 'IN'
					]
				];
			}

			$posts = \Timber\Timber::get_posts($args);
			if(count($posts))
			{
				$context['posts'] = $posts;

				switch($post_type){
					default:
					case('post'):
						Timber::render( array( 'partial/ajax-posts.twig' ), $context );
						break;
					case('testimonials'):
						Timber::render( array( 'partial/ajax-testimonials.twig' ), $context );
						break;
				}
			}else
			{
				echo "<h2 style='text-align: center'>No more posts to show!</h2>";
			}
		}else
		{
			echo "";
		}

		die();
	}

	/**
	 * This method will be used to power the Claim Calculator, and any connections to Infusionsoft we need to make.
	 */
	function claim_calculator_ajax()
	{
		header('Content-type: application/json');

		/** var wpdb $wpdb */
		global $wpdb;

		/**
		 * Create an instance of PDO that we'll use to query for the Posts
		 * TODO: Change this to PROD, READ-ONLY INFO once ready
		 * =====================================================================================================================
		 */
		$host = DB_HOST;
		$db   = DB_NAME;
		$user = DB_USER;
		$pass = DB_PASSWORD;
		$charset = 'utf8mb4';

		$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
		$opt = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false,
		];
		$pdo = new PDO($dsn, $user, $pass, $opt);

		$json_response = [
			'result' => true
		];

		/*
		 * Start by extracting some important information, namely their score since we're gonna display the page to
		 * them (results page) in just a little here.
		 */
		$points = $_POST['score'];
		$answer_list = $_POST['answers'];

		/*
		 * Next, get how the Results HTML will look (we'll return this in JSON headers)
		 */
		$context = Timber::get_context();

		$context['points'] = $points;

		/**
		 * This section will have us writing to the database a good bit, so pay attention.
		 *
		 * First, we are just going to assume we're always using Quiz ID 1, so fuck it.
		 *
		 * Next, create a record in wp_quiz_completed, which will house some stupid shit.
		 *
		 * Finally, we'll use the Completion ID to store all the results.
		 *
		 * And that's it.
		 *
		 */
		$complete_insert = $pdo->prepare("
			INSERT INTO wp_chained_completed (quiz_id, points, result_id, datetime, ip, user_id, snapshot, not_empty, source_url) 
			VALUES (1,?,0,NOW(),?,1,null,1,'')");

		$result_insert = $pdo->prepare("
			INSERT INTO wp_chained_user_answers (quiz_id, completion_id, question_id, answer,points)
			VALUES (1,?,?,?,?)");

		// Start by creating a new completed record
		$complete_insert->execute([
			$points, //points
			$_POST['email'] //ip
		]);

		// Next, grab the completed id, and loop through the answers now
		$completion_id = $pdo->lastInsertId();

		foreach($answer_list as $answer)
		{
			$result_insert->execute([
				$completion_id, 		//completion_id,
				$answer['question_id'], //question_id,
				$answer['answer_id'], 	//answer_id
				$answer['points']		//points
			]);
		}

		ob_start();

		Timber::render( array( 'calculator-results.twig' ), $context );

		$rendered_html = ob_get_contents();

		ob_end_clean();

		$json_response['results_html'] = $rendered_html;


		echo json_encode($json_response);

		die();
	}

	/**
	 * This method will be used to power the Search Posts AJAX (this is for like, auto-complete, and stuff)
	 */
	function search_posts_ajax()
	{
		/*if(!empty($results)){
			* @var Timber\Post $result
			foreach($results as $result)
			{
				$search_results[] = [
					'id' => $result->id,
					'title' => $result->title(),
					'preview' => (string)$result->preview(),
					'link' => $result->link()
				];
			}
		}*/
	}

	/**
	 * This method will search for Posts against the database and return the given results
	 *
	 * @param string $string
	 * @return array
	 */
	function searchPosts($string)
	{
		global $wpdb;

		$search_results = [];

		/*
		 * Make the search using wp_query, for now
		 */
		if($string && $string != "")
		{
			$results = \Timber\Timber::get_posts([
				'post_type' => ['post','page','news','case-results','faqs'],
				'posts_per_page' => -1,
				's' => $string
			]);
			if(!empty($results))
			{
				$search_results = $results;
			}
		}

		return $search_results;
	}
}

/**
 * I guess this is what actually initializes the whole thing--pretty cool.
 */
$JAL_Site = new StarterSite();