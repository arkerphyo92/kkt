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
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'kkt' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'FF>O0SPltsOkouFFr,g~T#Z^a~tG|<<JO`_*ae7lLu?@~v]Hreqgdnc3ED?UfP3)' );
define( 'SECURE_AUTH_KEY',  '}Kk{f5QSX#EQHWdV/p+ZwSk/~^tEz Xv,[EU~3(@<aq|F`LS| mApSmdH Px~<UL' );
define( 'LOGGED_IN_KEY',    '.T*mi%aS^u|:9,_sMcHto2U#D3Z00lEp(I@lQpsz@J?dcJ68M,c4b[WxrV#<$k5q' );
define( 'NONCE_KEY',        'Zi+` $? ]QODun#4hh1Wx /W4;e(%TQ*8_^SSHC_/fsgnEfFHa*v,auj2^$j>anh' );
define( 'AUTH_SALT',        'GFM{Qgp#` @ymXj?2efRd~0WEJdrH*cHHLoNuE$M~J<kC>YtnCL=-q {]1]H{!<_' );
define( 'SECURE_AUTH_SALT', 'lRxvsnuRU7xhQUo2ojtIieXI#NCQTv`n[Y&HBY05q;E8wuc;Y7;8vdehJbI`&f5h' );
define( 'LOGGED_IN_SALT',   '$#nBJ39l;^:[Ix5+ :<B-uX,/7%^N;.rgy$n%1L.^z3|N@)U#,dT]5zIose[FUr^' );
define( 'NONCE_SALT',       'h%|3AZ0Z+fOTyQ| tT_2ogTbRQ^cYR,S{*?K{KFgN)ZF>O|Wqz0^7%,:b`?()W(0' );

/**#@-*/

/**
 * WordPress database table prefix.
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
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
