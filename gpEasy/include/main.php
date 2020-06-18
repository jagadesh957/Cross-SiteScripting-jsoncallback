<?php


//$start_time = microtime();

define('is_running',true);
require_once('common.php');
common::EntryPoint(0);

/*
 *	Flow Control
 */


if( !empty($GLOBALS['config']['updating_message']) ){
	die($GLOBALS['config']['updating_message']);
}


$title = common::WhichPage();
$type = common::SpecialOrAdmin($title);
switch($type){

	case 'special':
		include_once($dataDir.'/include/special.php');
		$page = new special_display($title,$type);
	break;

	case 'admin':
		include_once($dataDir.'/include/admin/admin_display.php');
		$page = new admin_display($title,$type);
	break;

	default:
		if( common::LoggedIn() ){
			includeFile('tool/editing_page.php');
			$page = new editing_page($title,$type);
		}else{
			$page = new display($title,$type);
		}
	break;
}

common::RunOut();

//echo '<h2>'.microtime_diff($start_time,microtime()).'</h2>';






