<?php
/**
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl.txt
 * Copyright 2012-2020 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! defined( 'WPSSO_PLUGINDIR' ) ) {
	die( 'Do. Or do not. There is no try.' );
}

if ( ! class_exists( 'WpssoSubmenuTools' ) ) {
	require_once WPSSO_PLUGINDIR . 'lib/submenu/tools.php';
}

if ( ! class_exists( 'WpssoToolsSsoTools' ) && class_exists( 'WpssoAdmin' ) ) {

	class WpssoToolsSsoTools extends WpssoSubmenuTools {
	}
}
