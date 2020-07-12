<?php

// Uninstallation script: Removed options from the database.
// This code is executed if the plugin is uninstalled (deleted) through the
// WordPress Plugin Management interface.

if ( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
 }

delete_option('cleancodedVarnish_addr');
delete_option('cleancodedVarnish_port');
delete_option('cleancodedVarnish_secret');
delete_option('cleancodedVarnish_timeout');
delete_option('cleancodedVarnish_purge_url');
delete_option('cleancodedVarnish_update_pagenavi');
delete_option('cleancodedVarnish_update_commentnavi');
delete_option('cleancodedVarnish_use_adminport');
delete_option('cleancodedVarnish_vversion');

?>