<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'geely' );

/** Database username */
define( 'DB_USER', 'admin' );

/** Database password */
define( 'DB_PASSWORD', 'NOTEPADmax12!' );

/** Database hostname */
define( 'DB_HOST', 'geely-database.cbo8w4ekwbz1.sa-east-1.rds.amazonaws.com' );

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
define( 'AUTH_KEY',         '{#m2D.3+j*(yB,CS=vjU=K@3iEJ{lg>wA{.]!58$7#.S*HCObU!.&ldX%8q&^X>3' );
define( 'SECURE_AUTH_KEY',  'Pb#lV1wE%CVr:G|j6HQLsx@2TB6VLE?`p,NnkQAHLz#^p@d! Ybpj?(M`AZ3L;yA' );
define( 'LOGGED_IN_KEY',    'y)|wHj[3qc62>*_{|[~@4x,Nbc,%O`lr_,LJSKC u0w+_j-q_[y8h$fhN^<)ijeI' );
define( 'NONCE_KEY',        '5m,QvdwOcBZt^}5ujvr9e2zsqsH;xch:_MsSZTqUFt,:`wqxRpjqL&=b&zTx*2oH' );
define( 'AUTH_SALT',        'j5!ii?SFAwOq+guiRg|H ETIzY*epD4w+6`i@Rk9?wWZ>?$gTVZY2v|W7% 7c$V1' );
define( 'SECURE_AUTH_SALT', 'Xbl6ZCWsdIioxfaVB^|-tIkAz~Jfjud~K2i?.e3 U8PgFYULX~B<bW9;1L]D7Z*i' );
define( 'LOGGED_IN_SALT',   'lV!.AYmGk&k.,d%[_Pe-/lnm^G.|6`oQq63dAW+*!+{A8P-;V^YpLku[{O^?VK>[' );
define( 'NONCE_SALT',       'fp|nO}<qFu!g`yxG|D8,4-PEtJ,BGh >=fbzVJ2U@#:@*1e*j;8hmI2/Z,?1#:Ug' );
define( 'ACF_PRO_LICENSE', 'MjhkMTQ5ODIyY2IyNWRmOTlhNjEwMDg0MGUwNmI4NTU3OTQyNWEwOTZhN2M4MjJlOGQ0Mzll' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
 
/*define( 'WP_DEBUG', false );*/
/** Debug */
/*
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
@ini_set('display_errors', 1);
define('SCRIPT_DEBUG', true);
define('WP_CACHE', false);
*/

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
