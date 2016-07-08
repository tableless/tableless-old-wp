<?php
/**
 * The Bulk_Delete_By_Days class is moved to /includes/util/ directory in v5.5.
 *
 * This file is still here for backward compatibility.
 * Eventually once all the addons have been updated, this file will be removed.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// include the correct file
require_once Bulk_Delete::$PLUGIN_DIR . '/include/util/class-bulk-delete-by-days.php';
?>
