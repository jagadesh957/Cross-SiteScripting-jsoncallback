<?php

/**
 * $upload_extensions_allow and $upload_extensions_deny
 * Allow or deny the upload of files based on their file extensions
 */
$upload_extensions_allow = array();
$upload_extensions_deny = array();


/**
 * gp_browser_auth
 * Set to true to enable additional security by requiring a static browser identity for user session. Disabled by default since gpEasy 2.3.2
 * Enabling this feature may require administrators to log back in. If administrators report they are being logged out, then you may need to disable this feature
 */
define('gp_browser_auth',false);


/**
 * gp_require_encrypt
 * Set to true to require admin area encrypted login.
 * This will only disable un-encrypted login and not hide the option on the login page. When true, attempts to login without encryption will fail
 */
define('gp_require_encrypt',false);


/**
 * gp_remote_addons
 * Disable installation of remote addons
 */
define('gp_remote_addons',true);


/**
 * For use along with Admin_Permalink settings if Admin_Permalinks cannot finish hiding index.php alone
 * A false setting without the necessary mod_rewrite settings will break site navigation. See Admin_Permalinks for more information.
 */
 // define('gp_indexphp',false);


/**
 * Using setlocale() may enable more language specific ouptput for dates, times etc
 * http://php.net/manual/en/function.setlocale.php
 */
//setlocale(LC_ALL, 'en_US');


/**
 * service_provider_id
 * For gpEasy.com/Special_Services
 * Add your service provider id for tracking and to increase service provider activity level
 */
define('service_provider_id',false);

/**
 * gp_chmod_file
 * The mode used by chmod() for data files
 * http://php.net/manual/en/function.chmod.php
 */
define('gp_chmod_file',0666);

/**
 * gp_chmod_dir
 * The mode use by chmod for data folders
 * http://php.net/manual/en/function.chmod.php
 */
define('gp_chmod_dir',0755);


/**
 * gpdebug
 * Set to true to display php errors in the browser window.
 */
define('gpdebug',false);


/**
 * gpdebugjs
 * Set to true to display javascript errors in the browser window. Separate from gpdebug to allow specific debugging
 */
define('gpdebugjs',false);


/**
 * gptesting
 * Enable features currently under development
 */
define('gptesting',false);

