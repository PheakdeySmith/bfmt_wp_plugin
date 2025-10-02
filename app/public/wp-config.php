<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '.kPf0/Q3?[$Pe<;<JZAb4Cu}#+14Crv`h[#_%JLpBNe;Z|lNqc&!b-mkCW2#x-N4' );
define( 'SECURE_AUTH_KEY',   'r|6qvg&{=&vzgCcx#C(G=FEJ%p#-_~^2hL6Eb$L>J12<,cs@8B|4V>_COk=lOK[$' );
define( 'LOGGED_IN_KEY',     'I}!?}ngF}[+i~L4^z=3W:<QYw(fOtqxdR}Yp ,e#5h1z#Vm`K#sjB^ PP,eiv0,k' );
define( 'NONCE_KEY',         '>&VG?C=&zyTCfPHaE. OztO#PxdPj97lRq~UV:hb?xmDcb6$~@mQ1X$GHINc!X;f' );
define( 'AUTH_SALT',         'gzH/_wsNd|<J@|z`q3[4^9o*(1ySIu@1+;R@^Z|hk8#*zoAg%Q)Nl?XK0]?~1LLK' );
define( 'SECURE_AUTH_SALT',  '~;Q$q=b|[~M#{u$bdihqC}W.j$uk.g-J:^:>Z@R4g~4#Qn;BnrgFlbO`/{UVP$3x' );
define( 'LOGGED_IN_SALT',    '5]]SzysS;Vl!*G]f?1wk8q.^K5$:OacC&jdTFF%lYuS73w1}C^lhSsF@Bm2Y(K%|' );
define( 'NONCE_SALT',        '(kGMy2jUv6)W~t-f3$f=mX:jmxkuq?W<B50A`Bw5+PwHV9~trt4<nGjoE7Vme;0(' );
define( 'WP_CACHE_KEY_SALT', '0:$jtDeHA{y+?p9Qi[QF3<2)./!9ydDWKq>Z]%jyPl%Us=z$vO1lm:(7*Wvhyspm' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
