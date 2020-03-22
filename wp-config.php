<?php
/** Enable W3 Total Cache */
define('WP_CACHE', true); // Added by W3 Total Cache

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'tyfsite_prod' );

/** MySQL database username */
define( 'DB_USER', 'jonesact' );

/** MySQL database password */
define( 'DB_PASSWORD', 'Joneslaw2017!' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'MB^^dhMHTk]{axIzai!f+QIgq^4DCFf<&DfE?JcOg0,jr$[dabmb*.jsl0g$=[2B' );
define( 'SECURE_AUTH_KEY',  '14Wr{wp<AC<K|PVb24H|XMH[&f`bgE^*z7d79m4l5WTI|HbtX/hUV}WEI.Wl}iWI' );
define( 'LOGGED_IN_KEY',    '.)+2N>t2G42Vgqj/UPL<m=tvcUo?yQ`S4f#{U,frOWdi/!39-8l:M|Q!zPI?G*f}' );
define( 'NONCE_KEY',        'JGB_L<@w6;T#^aSt,5]sc@GSolpAGOq$k,!IZ{)=iizV^oAc$01}VK7#4lX9nD&6' );
define( 'AUTH_SALT',        '}!ibNn2~R;FvV|GGM@[.ey~F0ajuj6CrvVGy<0g&fqA`TA*BpFzrQbFQi*BJ/:@y' );
define( 'SECURE_AUTH_SALT', '6 ;^WO&zWcyl0#.msN!!Eeqyg5Q&%87^M/SRG/R#ptw J}z`lq~kcfo1zZ$-b Yy' );
define( 'LOGGED_IN_SALT',   '<r&%~5YI)8hNk!dxMvBJ@:hXea|{B24l|Qy(0UuY/h2C5{U4:y^y#h{4gs80K?r ' );
define( 'NONCE_SALT',       ')<c}Ze=6#8eUIy)0u;TV<ZlLrbh!AC}q{V|u Ri+j8Eu<l^/waB8E-JKz.?2p_4U' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
