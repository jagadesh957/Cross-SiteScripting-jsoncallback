<?php
defined('is_running') or die('Not an entry point...');


class gpupgrade{

	function gpupgrade(){
		global $config;

		includeFile('admin/admin_tools.php');


		if( version_compare($config['gpversion'],'1.6','<') ){
			die('Please upgrade to version 1.6, then 1.7 before upgrading to this version.');
		}

		if( version_compare($config['gpversion'],'1.7a2','<') ){
			die('Please upgrade to version 1.7 before upgrading to this version.');
		}

		if( version_compare($config['gpversion'],'1.8a1','<') ){
			die('Please upgrade to version 2.0 before upgrading to this version.');
		}

	}

}

