<?php
defined('is_running') or die('Not an entry point...');


class gpPlugin{


	/**
	 * Include a file in the current plugin directory
	 * @param string $file File to include relative to the current plugin directory
	 * @static
	 */
	function incl($file){
		global $addonPathCode;
		return include_once($addonPathCode.'/'.$file);
	}

	/**
	 * Alias of gpPlugin::incl()
	 */
	function inc($file){
		return gpPlugin::incl($file);
	}

	/**
	 * Similar to php's register_shutdown_function()
	 * This gpEasy specific version will keep track of the active plugin and make sure global path variables are set properly before callting $function
	 * Example: gpPlugin::RegisterShutdown(array('class_name','method_name'));  or  gpPlugin::RegisterShutdown(array('class_name','method_name'),'argument1'....);
	 *
	 */
	function RegisterShutdown(){
		global $addonFolderName;
		$args = func_get_args();
		register_shutdown_function(array('gpPlugin','ShutdownFunction'),$addonFolderName,$args);
	}

	/**
	 * Handle functions passed to gpPlugin::RegisterShutdown()
	 * This function should not be called directly.
	 */
	function ShutdownFunction($addonFolderName,$args){

		if( !is_array($args) || count($args) < 1 ){
			return false;
		}

		gpPlugin::SetDataFolder($addonFolderName);

		$function = array_shift($args);

		if( count($args) > 0 ){
			call_user_func_array( $function , $args );
		}else{
			call_user_func( $function  );
		}

		gpPlugin::ClearDataFolder();
	}


	/*
	 * Similar to wordpress apply_filters_ref_array()
	 *
	 */
	function Filter($hook, $args = array() ){
		global $config;

		if( !gpPlugin::HasHook($hook) ){
			if( isset($args[0]) ){
				return $args[0];
			}
			return false;
		}

		foreach($config['hooks'][$hook] as $hook_info){
			$args[0] = gpPlugin::ExecHook($hook,$hook_info,$args);
		}

		if( isset($args[0]) ){
			return $args[0];
		}
		return false;
	}


	function OneFilter($hook,$args=array()){
		global $config;

		if( !gpPlugin::HasHook($hook) ){
			return false;
		}

		$hook_info = end($config['hooks'][$hook]);

		return gpPlugin::ExecHook($hook,$hook_info,$args);
	}

	function Action($hook, $args = array() ){
		global $config;

		if( !gpPlugin::HasHook($hook) ){
			return;
		}

		foreach($config['hooks'][$hook] as $hook_info){
			gpPlugin::ExecHook($hook,$hook_info,$args);
		}
	}

	function HasHook($hook){
		global $config;
		if( empty($config['hooks']) || empty($config['hooks'][$hook]) ){
			return false;
		}
		return true;
	}

	function ArgReturn($args){
		if( is_array($args) && isset($args[0]) ){
			return $args[0];
		}

	}


	function ExecHook($hook,$hook_info,$args = array()){
		global $dataDir, $gp_current_hook;

		if( !is_array($args) ){
			$args = array($args);
		}

		//addonDir is deprecated as of 2.0b3
		if( isset($hook_info['addonDir']) ){
			gpPlugin::SetDataFolder($hook_info['addonDir']);
		}elseif( isset($hook_info['addon']) ){
			gpPlugin::SetDataFolder($hook_info['addon']);
		}

		$gp_current_hook[] = $hook;

		//value
		if( !empty($hook_info['value']) ){
			$args[0] = $hook_info['value'];
		}

		//data
		if( !empty($hook_info['data']) && file_exists($dataDir.$hook_info['data']) ){
			include($dataDir.$hook_info['data']);
		}

		//script
		if( isset($hook_info['script']) && file_exists($dataDir.$hook_info['script']) ){
			include_once($dataDir.$hook_info['script']);
		}


		//class & method
		if( !empty($hook_info['class']) && class_exists($hook_info['class']) ){
			$object = new $hook_info['class']($args);

			if( !empty($hook_info['method']) && method_exists($object, $hook_info['method']) ){
				$args[0] = call_user_func_array(array($object, $hook_info['method']), $args );
			}
		}elseif( !empty($hook_info['method']) && is_callable($hook_info['method']) ){
			$args[0] = call_user_func_array($hook_info['method'],$args);
		}

		array_pop( $gp_current_hook );
		gpPlugin::ClearDataFolder();

		if( isset($args[0]) ){
			return $args[0];
		}
		return false;
	}

	/**
	 * Set global path variables for the current addon
	 * $param string $addon_key Key used to identify a plugin uniquely in the configuration
	 *
	 */
	function SetDataFolder($addon_key){
		global $dataDir, $config;
		global $addonDataFolder,$addonCodeFolder; //deprecated
		global $addonRelativeCode,$addonRelativeData,$addonPathData,$addonPathCode,$addonFolderName,$addon_current_id;

		if( !isset($config['addons'][$addon_key]) ){
			return;
		}

		$data_folder = gpPlugin::GetDataFolder($addon_key);

		if( isset($config['addons'][$addon_key]['id']) ){
			$addon_current_id = $config['addons'][$addon_key]['id'];
		}

		$addonFolderName = $addon_key;
		$addonPathCode = $addonCodeFolder = $dataDir.'/data/_addoncode/'.$addon_key;
		$addonPathData = $addonDataFolder = $dataDir.'/data/_addondata/'.$data_folder;
		$addonRelativeCode = common::GetDir_Prefixed('/data/_addoncode/'.$addon_key);
		$addonRelativeData = common::GetDir_Prefixed('/data/_addondata/'.$data_folder);

	}

	function ClearDataFolder(){
		global $addonDataFolder,$addonCodeFolder; //deprecated
		global $addonRelativeCode,$addonRelativeData,$addonPathData,$addonPathCode,$addonFolderName,$addon_current_id;


		$addonFolderName = false;
		$addonDataFolder = $addonCodeFolder = false;
		$addonRelativeCode = $addonRelativeData = $addonPathData = $addonPathCode = $addon_current_id = false;

	}

	/*
	 * data_folder was briefly used during the development of 2.0.
	 * Some installations my still have plugins that rely on this setting
	 *
	 */
	function GetDataFolder($addon_key){
		global $config;
		if( isset($config['addons'][$addon_key]['data_folder']) ){
			return $config['addons'][$addon_key]['data_folder'];
		}
		return $addon_key;
	}

}


