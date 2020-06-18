<?php
defined('is_running') or die('Not an entry point...');

includeFile('admin/admin_addons_tool.php');

class admin_addon_install extends admin_addons_tool{

	var $developer_mode = false;
	var $source_folder_name;
	var $source_folder;
	var $addon_name;
	var $install_steps = array(1=>'addon_install_check',2=>'addon_install_copy',3=>'addon_install_save');
	var $config_cache;
	var $data_folder;


	//new
	var $upgrade_key = false;
	var $temp_folder_name;
	var $temp_folder_path;
	var $install_folder_name;
	var $install_folder_path;


	//plugin vs theme
	var $config_index = 'addons';
	var $addon_folder_name = '_addoncode';
	var $addon_folder;
	var $browser_path = 'Admin_Addons';
	var $can_install_links = true;


	//for remote install
	var $addon_type;



	/*
	 *
	 * Plugin vs Theme Installation
	 *
	 *
	 */

	function Init_PT(){
		global $config,$dataDir;

		if( !isset($config[$this->config_index]) ){
			$config[$this->config_index] = array();
		}

		$this->config =& $config[$this->config_index];
		$this->config_cache = $config;

		$this->addon_folder = $dataDir.'/data/'.$this->addon_folder_name;

		gpFiles::CheckDir($this->addon_folder);

	}


	/*
	 *
	 * Install Local Packages
	 *
	 *
	 */
	function admin_addon_install($cmd){
		global $langmessage;

		$this->Init_PT();

		if( !$this->InitInstall() ){
			return false;
		}
		$this->GetAddonData(); //for addonHistory


		$success = false;
		ob_start();
		switch($cmd){
			case 'step2':
				$success = $this->Install_Step2();
				$step = 2;
			break;
			case 'step3':
				$success = $this->Install_Step3();
				$step = 3;
			break;
			default:
				$success = $this->Install_Step1();
				$step = 1;
			break;
		}
		$content = ob_get_clean();

		if( $success ){
			$step++;
		}

		//output
		$this->Install_Progress($step-1);

		echo $content;

		$this->InstallForm($step);

		return true;
	}



	function InstallForm($step){
		global $langmessage;

		if( $step > 3 ){
			$this->Installed();
			return;
		}


		echo '<form action="'.common::GetUrl($this->browser_path).'" method="post">';
		echo '<input type="hidden" name="cmd" value="step'.$step.'" />';
		echo '<input type="hidden" name="source" value="'.htmlspecialchars($this->source_folder_name).'" />';
		echo '<input type="hidden" name="upgrade_key" value="'.htmlspecialchars($this->upgrade_key).'" />';

		if( isset($this->temp_folder_name) ){
			echo '<input type="hidden" name="temp_folder_name" value="'.htmlspecialchars($this->temp_folder_name).'" />';
		}
		echo '<input type="hidden" name="install_folder_name" value="'.htmlspecialchars($this->install_folder_name).'" />';


		echo '<p>';
		echo ' <input type="submit" name="" value="'.$langmessage['continue'].'" class="gpsubmit" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="gpcancel" />';
		if( $this->developer_mode ){
			echo ' <input type="hidden" name="mode" value="dev" />';
			echo ' &nbsp; <em>'.$langmessage['developer_install'].'</em>';
		}
		echo '</p>';

		echo '</form>';
	}

	function Installed(){
		global $langmessage;

		echo '<form action="'.common::GetUrl($this->browser_path).'" method="get">';
		echo '<input type="hidden" name="cmd" value="show" />';
		echo '<input type="hidden" name="addon" value="'.htmlspecialchars($this->install_folder_name).'" />';

		echo '<p>';
		echo sprintf($langmessage['installed'],$this->ini_contents['Addon_Name']);
		echo '</p>';
		echo '<p>';
		echo ' <input type="submit" name="aaa" value="'.$langmessage['continue'].'" class="gpsubmit"/>';
		if( $this->developer_mode ){
			echo ' <em>'.$langmessage['developer_install'].'</em>';
		}
		echo '</p>';
		echo '</form>';
	}

	function Install_Progress($step){
		global $langmessage;

		echo '<h2>';
		if( $this->upgrade_key ){
			echo $langmessage['upgrade'];
		}else{
			echo $langmessage['Install'];
		}
		if( $this->addon_name ){
			echo ' » ';
			echo htmlspecialchars($this->addon_name);
		}
		echo '</h2>';


		echo '<p id="addon_install_progress">';
		foreach($this->install_steps as $steps_step => $step_label){
			if( $steps_step < $step ){
				echo '<span class="completed">';
			}elseif( $steps_step == $step ){
				echo '<span class="current">';
			}else{
				echo '<span>';
			}
			echo ($steps_step).'. ';
			echo $langmessage[$step_label];
			echo '</span>';
		}
		echo '</p>';
	}



	/*
	 * Initialize the installation
	 * 	Check ini file
	 * 	Set folder variables
	 *
	 */
	function InitInstall(){
		global $dataDir, $langmessage, $gpversion;

		if( empty($_REQUEST['source']) ){
			message($langmessage['OOPS']);
			return false;
		}

		//developer mode
		if( isset($_REQUEST['mode']) && ($_REQUEST['mode'] == 'dev') ){
			if( !function_exists('symlink') ){
				message($langmessage['OOPS']);
				return false;
			}
			$this->developer_mode = true;
		}


		//init vars
		$this->source_folder_name = $_REQUEST['source'];
		$this->source_folder = $dataDir.'/addons/'.$_REQUEST['source'];
		$this->InitInstall_Vars();


		//check folders
		if( !file_exists($this->source_folder) ){
			message( sprintf($langmessage['File_Not_Found'],' <em>'.$this->source_folder.'</em>') );
			return false;
		}


		//get ini contents
		if( !$this->Install_Ini($this->source_folder) ){
			return false;
		}

		return true;
	}

	function InitInstall_Vars(){
		global $dataDir;

		if( !empty($_POST['temp_folder_name']) ){
			$this->temp_folder_name = $_POST['temp_folder_name'];
			$this->temp_folder_path = $this->addon_folder.'/'.$this->temp_folder_name;
		}

		if( !empty($_POST['upgrade_key']) ){
			$this->upgrade_key = $_POST['upgrade_key'];
		}

		if( !empty($_POST['install_folder_name']) ){
			$this->install_folder_name = $_POST['install_folder_name'];
			$this->install_folder_path = $this->addon_folder.'/'.$this->install_folder_name;
		}


		if( isset($this->config[$this->install_folder_name]['data_folder']) ){
			$this->data_folder = $this->config[$this->install_folder_name]['data_folder'];
		}else{
			$this->data_folder = $this->install_folder_name;
		}
	}


	function Install_Ini($ini_dir){
		global $langmessage, $dataDir, $dirPrefix;


		$ini_file = $ini_dir.'/Addon.ini';

		if( !file_exists($ini_file) ){
			message( sprintf($langmessage['File_Not_Found'],' <em>'.$ini_file.'</em>') );
			return false;
		}

		$variables = array(
					'{$addon}'				=> $this->install_folder_name,
					'{$dataDir}'			=> $dataDir,
					'{$dirPrefix}'			=> $dirPrefix,
					'{$addonRelativeData}'	=> common::GetDir('/data/_addondata/'.$this->data_folder),
					'{$addonRelativeCode}'	=> common::GetDir('/data/'.$this->addon_folder_name.'/'.$this->install_folder_name),
					);


		//get ini contents
		$this->ini_contents = gp_ini::ParseFile($ini_file,$variables);

		if( !$this->ini_contents ){
			message($langmessage['Ini_Error'].' '.$langmessage['Ini_Submit_Bug']);
			return false;
		}

		if( !isset($this->ini_contents['Addon_Name']) ){
			message($langmessage['Ini_No_Name'].' '.$langmessage['Ini_Submit_Bug']);
			return false;
		}

		if( isset($this->ini_contents['Addon_Unique_ID']) && !is_numeric($this->ini_contents['Addon_Unique_ID']) ){
			message('Invalid Unique ID');
			return false;
		}

		$this->addon_name = $this->ini_contents['Addon_Name'];

		return true;
	}


	/*
	 * Determine if the addon (identified by $ini_info and $source_folder) is an upgrade to an existing addon
	 *
	 * @return mixed
	 */
	function UpgradePath($ini_info,$config_key='addons'){
		global $dataDir, $config;

		if( !isset($config[$config_key]) ){
			return false;
		}

		//by id
		if( isset($ini_info['Addon_Unique_ID']) ){
			foreach($config[$config_key] as $addon_key => $data){
				if( !isset($data['id']) || !is_numeric($data['id']) ){
					continue;
				}

				if( (int)$data['id'] == (int)$ini_info['Addon_Unique_ID'] ){
					return $addon_key;
				}
			}
		}

		//by name
		if( isset($ini_info['Addon_Name']) ){
			foreach($config[$config_key] as $addon_key => $data){
				if( $data['name'] == $ini_info['Addon_Name'] ){
					return $addon_key;
				}
			}
		}

		return false;
	}



	/*
	 * Step 1
	 * Check the contents of the addon that is to be installed
	 *
	 */

	function Install_Step1(){
		global $dataDir, $langmessage, $gpversion;

		//start with a clean addoncode folder
		$this->CleanInstallFolder();
		$this->upgrade_key = $this->UpgradePath($this->ini_contents);



		//warn about unique id
		if( !isset($this->ini_contents['Addon_Unique_ID']) ){
			echo '<p class="gp_notice">';
			echo $langmessage['Ini_No_ID'];
			echo '</p>';

			if( $this->upgrade_key && isset($this->config[$this->upgrade_key]['id']) ){
				$name = $this->config[$this->upgrade_key]['name'];
				echo '<p class="gp_warning">';
				echo sprintf($langmessage['incorrect_update'],' <em>'.$name.'</em> ');
				echo '</p>';
				return false;
			}

		}

		if( !$this->Install_CheckName($this->ini_contents['Addon_Name']) ){
			return false;
		}


		//Check Versions
		if( isset($this->ini_contents['min_gpeasy_version']) ){
			if(version_compare($this->ini_contents['min_gpeasy_version'], $gpversion,'>') ){
				echo '<p class="gp_warning">';
				echo sprintf($langmessage['min_version'],$this->ini_contents['min_gpeasy_version']);
				echo ' '.$langmessage['min_version_upgrade'];
				echo '</p>';
				return false;
			}
		}

		echo '<p>';
		echo sprintf($langmessage['Selected_Install'],' <em>'.htmlspecialchars($this->ini_contents['Addon_Name']).'</em> ',' <em>'.htmlspecialchars($this->source_folder).'</em>');
		echo '</p>';


		if( !empty($this->ini_contents['About']) ){
			echo '<div id="addon_about">';
			echo '<h3>'.$langmessage['about'].'</h3>';
			echo strip_tags($this->ini_contents['About'],'<div><p><a><b><br/><span><tt><em><i><b><sup><sub><strong><u>');
			echo '</div>';
		}


		//Addon Custom Install_Check()
		$checkFile = $this->source_folder.'/Install_Check.php';
		if( file_exists($checkFile) ){
			include($checkFile);
			if( function_exists('Install_Check') ){
				if( !Install_Check() ){
					return false;
				}
			}
		}

		return true;
	}

	function Install_CheckName($check_name){
		global $langmessage;

		//check for duplicate name
		foreach($this->config as $addon_key => $data){
			if( $this->upgrade_key && ($this->upgrade_key == $addon_key) ){
				continue;
			}

			if( $data['name'] == $check_name ){
				echo '<p class="gp_warning">';
				echo sprintf($langmessage['already_installed'],' <em>'.$check_name.'</em> ');
				echo '</p>';
				return false;
			}
		}

		return true;
	}




	/*
	 * Step 2
	 * Copy the files (or create symlink) of the addon to be installed to /data/_addoncode/<addon_folder>
	 *
	 *
	 */
	function Install_Step2(){
		global $langmessage, $dataDir;


		if( !$this->developer_mode ){

			if( $this->upgrade_key ){
				$this->install_folder_name = $this->upgrade_key;
				$this->temp_folder_name = $this->NewTempFolder();
				$copy_to = $this->addon_folder.'/'.$this->temp_folder_name;
			}else{
				$this->install_folder_name = $this->NewTempFolder();
				$copy_to = $this->addon_folder.'/'.$this->install_folder_name;
			}

			if( !admin_addon_install::CopyAddonDir($this->source_folder,$copy_to) ){
				echo '<p>';
				echo $langmessage['OOPS'];
				echo '</p>';
				return false;
			}

			echo '<p>';
			echo $langmessage['copied_addon_files'];
			echo '</p>';

			return true;
		}


		//developer mode
		//...


		if( $this->upgrade_key ){
			$this->install_folder_name = $this->upgrade_key;
			echo '<p>';
			echo $langmessage['copied_addon_files'];
			echo '</p>';
			return true;
		}

		$this->install_folder_name = $this->NewTempFolder();
		$this->install_folder_path = $this->addon_folder.'/'.$this->install_folder_name;

		if( !symlink($this->source_folder,$this->install_folder_path) ){
			echo '<p>';
			echo $langmessage['OOPS'];
			echo '</p>';
			return false;
		}

		echo '<p>';
		echo $langmessage['copied_addon_files'];
		echo '</p>';
		return true;

	}


	function NewTempFolder(){
		global $dataDir;

		do{
			$folder = common::RandomString(7,false);
			$full_dest = $this->addon_folder.'/'.$folder;

		}while( is_numeric($folder) || isset($this->config[$folder]) || file_exists($full_dest) );

		return $folder;
	}


	function CopyAddonDir($fromDir,$toDir){

		$dh = @opendir($fromDir);
		if( !$dh ){
			return false;
		}

		if( !gpFiles::CheckDir($toDir) ){
			message('Copy failed: '.$fromDir.' to '.$toDir.' (1)');
			return false;
		}


		while( ($file = readdir($dh)) !== false){

			if( strpos($file,'.') === 0){
				continue;
			}

			$fullFrom = $fromDir.'/'.$file;
			$fullTo = $toDir.'/'.$file;


			//directories
			if( is_dir($fullFrom) ){
				if( !admin_addon_install::CopyAddonDir($fullFrom,$fullTo) ){
					closedir($dh);
					return false;
				}
				continue;
			}

			//files
			//If the destination file already exists, it will be overwritten.
			if( !copy($fullFrom,$fullTo) ){
				message('Copy failed: '.$fullFrom.' to '.$fullTo.' (2)');
				closedir($dh);
				return false;
			}
		}
		closedir($dh);


		return true;
	}



	/*
	 *
	 * Step 3
	 * 		Update configuration
	 *
	 */

	function Install_Step3(){
		global $langmessage, $dataDir, $config;

		if( empty($this->install_folder_name) ){
			message($langmessage['OOPS']);
			return false;
		}


		if( $this->upgrade_key && !$this->developer_mode ){
			if( !file_exists($this->install_folder_path) ){
				message($langmessage['OOPS']);
				return false;
			}

			if( empty($this->temp_folder_name) || !file_exists($this->temp_folder_path) ){
				message($langmessage['OOPS']);
				return false;
			}
		}

		$old_config = $config;
		$this->Step3_Links();


		if( !isset($this->config[$this->install_folder_name]) ){
			$this->config[$this->install_folder_name] = array();
		}

		echo '<p>';
		echo 'Saving Settings';
		echo '</p>';


		//general configuration
		$this->UpdateConfigInfo('Addon_Name','name');
		$this->UpdateConfigInfo('Addon_Version','version');
		$this->UpdateConfigInfo('Addon_Unique_ID','id');

		if( $this->can_install_links ){
			$this->UpdateConfigInfo('editable_text','editable_text');
			$this->UpdateConfigInfo('html_head','html_head');
		}

		if( !$this->Step3_Folders() ){
			message($langmessage['OOPS']);
			$config = $old_config;
			return false;
		}

		if( !admin_tools::SaveAllConfig() ){
			message($langmessage['OOPS']);
			$config = $old_config;
			return false;
		}


		/*
		 * History
		 */


		$history = array();
		$history['name'] = $this->config[$this->install_folder_name]['name'];
		$history['action'] = 'installed';
		if( isset($this->config[$this->install_folder_name]['id']) ){
			$history['id'] = $this->config[$this->install_folder_name]['id'];
		}
		$history['time'] = time();

		$this->addonHistory[] = $history;
		$this->SaveAddonData();


		//completed install, clean up /data/_addoncode/ folder
		$this->CleanInstallFolder();

		return true;
	}


	function Step3_Folders(){

		if( $this->developer_mode ){
			return true;
		}

		if( !$this->upgrade_key ){
			return true;
		}


		$trash_name = $this->NewTempFolder();
		$trash_path = $this->addon_folder.'/'.$trash_name;
		if( !rename($this->install_folder_path,$trash_path) ){
			return false;
		}

		//rename temp folder
		if( !rename($this->temp_folder_path,$this->install_folder_path) ){
			rename($trash_path,$this->install_folder_path);
			return false;
		}
		return true;
	}


	function Step3_Links(){
		global $langmessage, $dataDir, $config;

		if( !$this->can_install_links ){
			return;
		}

		echo '<p>';
		echo 'Adding Gadgets';
		echo '</p>';

		//needs to be before other gadget functions
		$installedGadgets = $this->GetInstalledComponents($config['gadgets'],$this->install_folder_name);

		//echo showArray($this->ini_contents);
		$gadgets = $this->ExtractFromInstall($this->ini_contents,'Gadget:');
		$gadgets = $this->CleanGadgets($gadgets);
		$this->PurgeExisting($config['gadgets'],$gadgets);
		$this->AddToConfig($config['gadgets'],$gadgets);

		//remove gadgets that were installed but are no longer part of package
		$gadgetNames = array_keys($gadgets);
		$toRemove = array_diff($installedGadgets,$gadgetNames);
		$this->RemoveFromHandlers($toRemove);

		//add new gadgets to GetAllGadgets handler
		$toAdd = array_diff($gadgetNames,$installedGadgets);
		$this->AddToHandlers($toAdd);


		echo '<p>';
		echo 'Adding Links';
		echo '</p>';

		//admin links
		$Admin_Links = $this->ExtractFromInstall($this->ini_contents,'Admin_Link:');
		$Admin_Links = $this->CleanLinks($Admin_Links,'Admin_');
		$this->PurgeExisting($config['admin_links'],$Admin_Links);
		$this->AddToConfig($config['admin_links'],$Admin_Links);



		//special links
		$Special_Links = $this->ExtractFromInstall($this->ini_contents,'Special_Link:');
		$Special_Links = $this->CleanLinks($Special_Links,'Special_','special');
		$this->UpdateSpecialLinks($Special_Links);


		echo '<p>';
		echo 'Adding Hooks';
		echo '</p>';


		//generic hooks
		$this->AddHooks();

	}



	function UpdateConfigInfo($ini_var,$config_var){

		if( isset($this->ini_contents[$ini_var]) ){
			$this->config[$this->install_folder_name][$config_var] = $this->ini_contents[$ini_var];
		}elseif( isset($this->config[$this->install_folder_name][$config_var]) ){
			unset($this->config[$this->install_folder_name][$config_var]);
		}
	}


	function UpdateSpecialLinks($Special_Links){
		global $gp_index, $gp_titles, $gp_menu, $langmessage;

		//purge links no longer defined ... similar to PurgeExisting()
		foreach($gp_index as $linkName => $index){
			$linkInfo = $gp_titles[$index];
			if( !isset($linkInfo['addon']) ){
				continue;
			}
			if( $linkInfo['addon'] !== $this->install_folder_name ){
				continue;
			}

			if( isset($Special_Links[$linkName]) ){
				continue;
			}

			unset($gp_index[$linkName]);
			unset($gp_titles[$index]);
			if( isset($gp_menu[$index]) ){
				unset($gp_menu[$index]);
			}
		}

		$lower_titles = array_change_key_case($gp_index,CASE_LOWER);

		//add new links ... similar to AddToConfig()
		foreach($Special_Links as $new_title => $linkInfo){

			$lower_title = strtolower($new_title);

			if( isset($lower_titles[$lower_title]) ){

				$addlink = true;
				$index = $lower_titles[$lower_title];
				$curr_info = $gp_titles[$index];

				if( !isset($curr_info['addon']) || $this->install_folder_name === false ){
					$addlink = false;
				}elseif( $curr_info['addon'] != $this->install_folder_name ){
					$addlink = false;
				}

				if( !$addlink ){
					echo '<p class="gp_notice">';
					echo sprintf($langmessage['addon_key_defined'],' <em>Special_Link: '.$new_title.'</em>');
					echo '<p>';
					continue;
				}

				//this will overwrite things like label which are at times editable by users
				//$AddTo[$new_title] = $linkInfo + $AddTo[$new_title];


			}else{
				$index = common::NewFileIndex();
				$gp_index[$new_title] = $index;
				$gp_titles[$index] = $linkInfo;
			}

			$this->UpdateLinkInfo($gp_titles[$index],$linkInfo);
		}
	}


	function AddHooks(){

		$installed = array();
		foreach($this->ini_contents as $hook => $hook_args){
			if( !is_array($hook_args) ){
				continue;
			}

			if( strpos($hook,'Gadget:') === 0
				|| strpos($hook,'Admin_Link:') === 0
				|| strpos($hook,'Special_Link:') === 0
				){
					continue;
			}

			if( $this->AddHook($hook,$hook_args) ){
				$installed[$hook] = $hook;
			}
		}

		$this->CleanHooks($this->install_folder_name,$installed);
	}

	function CleanHooks($addon,$keep_hooks = array()){
		global $config;

		if( !isset($config['hooks']) ){
			return;
		}

		foreach($config['hooks'] as $hook_name => $hook_array){

			foreach($hook_array as $hook_dir => $hook_args){

				//not cleaning other addons
				if( $hook_dir != $addon ){
					continue;
				}

				if( !isset($keep_hooks[$hook_name]) ){
					unset($config['hooks'][$hook_name][$hook_dir]);
					//message('remove this hook: '.$hook_name);
				}
			}
		}

		//reduce further if empty
		foreach($config['hooks'] as $hook_name => $hook_array){
			if( empty($hook_array) ){
				unset($config['hooks'][$hook_name]);
			}
		}

	}

	function AddHook($hook,$hook_args){
		global $config;

		$add = array();
		$this->UpdateLinkInfo($add,$hook_args);
		$config['hooks'][$hook][$this->install_folder_name] = $add;

		return true;
	}


	//extract the configuration type (extractArg) from $Install
	function ExtractFromInstall(&$Install,$extractArg){
		if( !is_array($Install) || (count($Install) <= 0) ){
			return array();
		}

		$extracted = array();
		$removeLength = strlen($extractArg);

		foreach($Install as $InstallArg => $ArgInfo){
			if( strpos($InstallArg,$extractArg) !== 0 ){
				continue;
			}
			$extractName = substr($InstallArg,$removeLength);
			if( !$this->CheckName($extractName) ){
				continue;
			}

			$extracted[$extractName] = $ArgInfo;
		}
		return $extracted;
	}



	function CheckName($name){

		$test = str_replace(array('.','_',' '),array(''),$name );
		if( empty($test) || !ctype_alnum($test) ){
			echo '<p class="gp_notice">';
			echo 'Could not install <em>'.$name.'</em>. Link and gadget names can only contain alphanumeric characters with underscore "_", dot "." and space " " characters.';
			echo '</p>';
			return false;
		}
		return true;
	}




	/*
	 * Add to $AddTo
	 * 	Don't add elements already defined by gpEasy or other addons
	 *
	 */
	function AddToConfig(&$AddTo,$New_Config){
		global $langmessage;

		if( !is_array($New_Config) || (count($New_Config) <= 0) ){
			return;
		}

		$lower_add_to = array_change_key_case($AddTo,CASE_LOWER);

		foreach($New_Config as $Config_Key => $linkInfo){

			$lower_key = strtolower($Config_Key);

			if( isset($lower_add_to[$lower_key]) ){
				$addlink = true;

				if( !isset($lower_add_to[$lower_key]['addon']) || $this->install_folder_name === false ){
					$addlink = false;
				}elseif( $lower_add_to[$lower_key]['addon'] != $this->install_folder_name ){
					$addlink = false;
				}

				if( !$addlink ){
					echo '<p class="gp_notice">';
					echo sprintf($langmessage['addon_key_defined'],' <em>'.$Config_Key.'</em>');
					echo '<p>';
					continue;
				}

				//this will overwrite things like label which are at times editable by users
				//$AddTo[$Config_Key] = $linkInfo + $AddTo[$Config_Key];

			}else{
				$AddTo[$Config_Key] = $linkInfo;
			}

			$this->UpdateLinkInfo($AddTo[$Config_Key],$linkInfo);
		}
	}

	function UpdateLinkInfo(&$link_array,$new_info){

		$link_array['addon'] = $this->install_folder_name;
		if( !empty($new_info['script']) ){
			$link_array['script'] = '/data/_addoncode/'.$this->install_folder_name .'/'.$new_info['script'];
		}else{
			unset($link_array['script']);
		}
		if( !empty($new_info['data']) ){
			$link_array['data'] = '/data/_addondata/'.$this->data_folder.'/'.$new_info['data'];
		}else{
			unset($link_array['data']);
		}
		if( !empty($new_info['class']) ){
			$link_array['class'] = $new_info['class'];
		}else{
			unset($link_array['class']);
		}
		if( !empty($new_info['method']) ){

			$method = $new_info['method'];
			if( strpos($method,'::') > 0 ){
				$method = explode('::',$method);
			}

			$link_array['method'] = $method;
		}else{
			unset($link_array['method']);
		}

		if( !empty($new_info['value']) ){
			$link_array['value'] = $new_info['value'];
		}else{
			unset($link_array['value']);
		}

	}




	/*
	 * Purge Links from $purgeFrom that were once defined for $this->install_folder_name
	 *
	 */
	function PurgeExisting(&$purgeFrom,$NewLinks){

		if( $this->install_folder_name === false || !is_array($purgeFrom) ){
			return;
		}

		foreach($purgeFrom as $linkName => $linkInfo){
			if( !isset($linkInfo['addon']) ){
				continue;
			}
			if( $linkInfo['addon'] !== $this->install_folder_name ){
				continue;
			}

			if( isset($NewLinks[$linkName]) ){
				continue;
			}

			unset($purgeFrom[$linkName]);
		}

	}


	/*
	 * Make sure the extracted links are valid
	 *
	 */
	function CleanLinks(&$links,$prefix,$linkType=false){

		$lower_prefix = strtolower($prefix);

		if( !is_array($links) || (count($links) <= 0) ){
			return array();
		}

		$result = array();
		foreach($links as $linkName => $linkInfo){
			if( !isset($linkInfo['script']) ){
				continue;
			}
			if( !isset($linkInfo['label']) ){
				continue;
			}

			if( (strpos($linkName,$prefix) !== 0) && (strpos(strtolower($linkName),$lower_prefix) !== 0) ){
				$linkName = $prefix.$linkName;
			}


			$result[$linkName] = array();
			$result[$linkName]['script'] = $linkInfo['script'];
			$result[$linkName]['label'] = $linkInfo['label'];

			if( isset($linkInfo['class']) ){
				$result[$linkName]['class'] = $linkInfo['class'];
			}

			/*	method only available for gadgets as of 1.7b1
			if( isset($linkInfo['method']) ){
				$result[$linkName]['method'] = $linkInfo['method'];
			}
			*/

			if( $linkType ){
				$result[$linkName]['type'] = $linkType;
			}

		}

		return $result;
	}



	/*
	 * Gadget Functions
	 *
	 *
	 */
	function AddToHandlers($gadgets){
		global $gpLayouts;

		if( !is_array($gpLayouts) || !is_array($gadgets) ){
			return;
		}

		foreach($gpLayouts as $layout => $containers){
			if( !is_array($containers) ){
				continue;
			}

			if( isset($containers['handlers']['GetAllGadgets']) ){
				$container =& $gpLayouts[$layout]['handlers']['GetAllGadgets'];
				if( !is_array($container) ){
					$container = array();
				}
				$container = array_merge($container,$gadgets);
			}
		}
	}


	//remove gadgets from $gpLayouts
	function RemoveFromHandlers($gadgets){
		global $gpLayouts;

		if( !is_array($gpLayouts) || !is_array($gadgets) ){
			return;
		}


		foreach($gpLayouts as $theme => $containers){
			if( !is_array($containers) || !isset($containers['handlers']) || !is_array($containers['handlers']) ){
				continue;
			}
			foreach($containers['handlers'] as $container => $handlers){
				if( !is_array($handlers) ){
					continue;
				}

				foreach($handlers as $index => $handle){
					$pos = strpos($handle,':');
					if( $pos > 0 ){
						$handle = substr($handle,0,$pos);
					}

					foreach($gadgets as $gadget){
						if( $handle === $gadget ){
							$handlers[$index] = false; //set to false
						}
					}
				}

				$handlers = array_diff($handlers, array(false)); //remove false entries
				$handlers = array_values($handlers); //reset keys
				$gpLayouts[$theme]['handlers'][$container] = $handlers;
			}
		}
	}


	/*
	 * similar to CleanLinks()
	 *
	 */
	function CleanGadgets(&$gadgets){
		global $gpOutConf, $langmessage, $config;

		if( !is_array($gadgets) || (count($gadgets) <= 0) ){
			return array();
		}

		$result = array();
		foreach($gadgets as $gadgetName => $gadgetInfo){

			//check against $gpOutConf
			if( isset($gpOutConf[$gadgetName]) ){
				echo '<p class="gp_notice">';
				echo sprintf($langmessage['addon_key_defined'],' <em>Gadget: '.$gadgetName.'</em>');
				echo '<p>';
				continue;
			}

			//check against other gadgets
			if( isset($config['gadgets'][$gadgetName]) && ($config['gadgets'][$gadgetName]['addon'] !== $this->install_folder_name) ){
				echo '<p class="gp_notice">';
				echo sprintf($langmessage['addon_key_defined'],' <em>Gadget: '.$gadgetName.'</em>');
				echo '<p>';
				continue;
			}


			$temp = array();
			if( isset($gadgetInfo['script']) ){
				$temp['script'] = $gadgetInfo['script'];
			}
			if( isset($gadgetInfo['class']) ){
				$temp['class'] = $gadgetInfo['class'];
			}
			if( isset($gadgetInfo['data']) ){
				$temp['data'] = $gadgetInfo['data'];
			}
			if( isset($gadgetInfo['method']) ){
				$temp['method'] = $gadgetInfo['method'];
			}

			if( count($temp) > 0 ){
				$result[$gadgetName] = $temp;
			}
		}

		return $result;
	}







	/*
	 * Remote Install Functions
	 *
	 *
	 *
	 */


	function RemoteInstallMain($cmd){
		global $langmessage;

		$this->Init_PT();
		$this->GetAddonData(); //for addonHistory
		if( !$this->RemoteInit() ){
			return;
		}

		$success = false;
		ob_start();
		switch($cmd){
			case 'remote_install3':
				$success = $this->RemoteInstall3();
				$step = 3;
			break;

			case 'remote_install2':
				$success = $this->RemoteInstall2();
				$step = 2;
			break;
			default:
				$success = $this->RemoteInstall1();
				$step = 1;
			break;
		}
		$content = ob_get_clean();

		if( $success ){
			$step++;
		}

		$this->Install_Progress($step-1);

		echo $content;

		$this->RemoteInstall_Form($step);

	}

	function RemoteInstall_Form($step){
		global $langmessage;

		if( $step > 3 ){
			$this->Installed();
			return;
		}

		echo '<form action="'.common::GetUrl($this->browser_path).'" method="post">';
		echo '<input type="hidden" name="name" value="'.htmlspecialchars($_REQUEST['name']).'" />';
		echo '<input type="hidden" name="type" value="'.htmlspecialchars($_REQUEST['type']).'" />';
		echo '<input type="hidden" name="version" value="'.htmlspecialchars($_REQUEST['version']).'" />';
		echo '<input type="hidden" name="file" value="'.htmlspecialchars($_REQUEST['file']).'" />';
		echo '<input type="hidden" name="id" value="'.htmlspecialchars($_REQUEST['id']).'" />';
		echo '<input type="hidden" name="md5" value="'.htmlspecialchars($_REQUEST['md5']).'" />';
		echo '<input type="hidden" name="upgrade_key" value="'.htmlspecialchars($this->upgrade_key).'" />';

		if( isset($this->temp_folder_name) ){
			echo '<input type="hidden" name="temp_folder_name" value="'.htmlspecialchars($this->temp_folder_name).'" />';
		}
		echo '<input type="hidden" name="install_folder_name" value="'.htmlspecialchars($this->install_folder_name).'" />';


		echo '<p>';
		echo '<input type="hidden" name="cmd" value="remote_install'.$step.'" />';
		echo ' <input type="submit" name="" value="'.$langmessage['continue'].'" class="gpsubmit" />';
		echo ' <input type="submit" name="cmd" value="'.$langmessage['cancel'].'" class="gpcancel" />';
		echo '</p>';

		echo '</form>';
	}

	function RemoteInit(){
		global $dataDir, $langmessage;

		if( empty($_REQUEST['name'])
			|| empty($_REQUEST['type'])
			|| empty($_REQUEST['version'])
			|| empty($_REQUEST['file'])
			|| empty($_REQUEST['md5'])
			|| !ctype_alnum($_REQUEST['md5'])
			|| empty($_REQUEST['id'])
			|| !is_numeric($_REQUEST['id'])
			){
				message($langmessage['OOPS']);
				return false;
		}
		if( $_REQUEST['type'] != 'plugin' && $_REQUEST['type'] != 'theme' ){
			message($langmessage['OOPS']);
			return false;
		}

		if( !admin_tools::CanRemoteInstall() ){
			message($langmessage['OOPS']);
			return false;
		}

		$addonName =& $_REQUEST['name'];
		$this->addon_name = $addonName;

		$this->InitInstall_Vars();

		return true;
	}


	function RemoteInstall1(){
		global $langmessage;

		//start with a clean addoncode folder
		$this->CleanInstallFolder();


		//upgrade?
		foreach($this->config as $addon_key => $addon_info){
			if( !isset($addon_info['id']) ){
				continue;
			}
			if( $addon_info['id'] == $_REQUEST['id'] ){
				$this->upgrade_key = $addon_key;
			}
		}

		if( !$this->Install_CheckName($this->addon_name) ){
			return false;
		}


		echo '<p class="gp_notice">';
		echo $langmessage['Addon_Install_Warning'];
		echo '</p>';

		echo '<p>';
		echo sprintf($langmessage['Selected_Install'],' <em>'.htmlspecialchars($this->addon_name).'</em> ',' gpEasy.com');
		echo '</p>';


		return true;
	}


	function RemoteInstall2(){
		global $dataDir, $langmessage, $addonBrowsePath;

		includeFile('tool/RemoteGet.php');


		if( $this->upgrade_key ){
			$this->install_folder_name = $this->upgrade_key;
			$this->temp_folder_name = $this->NewTempFolder();
			$copy_to = $this->addon_folder.'/'.$this->temp_folder_name;

		}else{
			$this->install_folder_name = $this->NewTempFolder();
			$copy_to = $this->addon_folder.'/'.$this->install_folder_name;
		}

		//download
		$download_link = $addonBrowsePath;
		if( $_POST['type'] == 'theme' ){
			$download_link .= '/Special_Addon_Themes';
		}else{
			$download_link .= '/Special_Addon_Plugins';
		}
		$download_link .= '?cmd=download&id='.rawurlencode($_POST['id']).'&version='.rawurlencode($_POST['version']).'&file='.rawurlencode($_POST['file']);
		$result = gpRemoteGet::Get_Successful($download_link);
		if( !$result ){
			message($langmessage['download_failed'].' (1)');
			return false;
		}

		//check md5
		if( md5($result) != $_POST['md5'] ){
			message($langmessage['OOPS'].' '.$langmessage['Download_Unverified']);
			return false;
		}

		//save contents
		$tempfile = $this->tempfile();
		if( !gpFiles::Save($tempfile,$result) ){
			message($langmessage['download_failed'].' (2)');
			return false;
		}

		// Unzip uses a lot of memory, but not this much hopefully
		@ini_set('memory_limit', '256M');
		includeFile('thirdparty/pclzip-2-8-2/pclzip.lib.php');
		$archive = new PclZip($tempfile);
		$archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING);
		unlink($tempfile);

		if( !$this->write_package($copy_to,$archive_files) ){
			return false;
		}

		echo '<p>';
		echo $langmessage['copied_addon_files'];
		echo '</p>';

		return true;
	}

	function RemoteInstall3(){

		if( !empty($this->temp_folder_name) ){
			$ini_folder = $this->temp_folder_path;
		}else{
			$ini_folder = $this->install_folder_path;
		}

		if( !$this->Install_Ini($ini_folder) ){
			return false;
		}

		return $this->Install_Step3();
	}



	function tempfile(){
		global $dataDir;

		do{
			$tempfile = $dataDir.'/data/_temp/'.rand(1000,9000).'.zip';
		}while(file_exists($tempfile));

		return $tempfile;
	}


	function write_package($dir,&$files){

		if( !gpFiles::CheckDir($dir) ){
			echo '<p class="gp_warning">';
			echo sprintf($langmessage['COULD_NOT_SAVE'],$folder);
			echo '</p>';
			return false;
		}

		//get archive root
		$archive_root = false;
		foreach( $files as $file ){
			if( strpos($file['filename'],'/Addon.ini') !== false ){
				$root = dirname($file['filename']);
				if( !$archive_root || ( strlen($root) < strlen($archive_root) ) ){
					$archive_root = $root;
				}
			}
		}
		$archive_root_len = strlen($archive_root);


		foreach($files as $file_info){

			$filename = $file_info['filename'];

			if( $archive_root ){
				if( strpos($filename,$archive_root) !== 0 ){
					continue;

/*
					trigger_error('$archive_root not in path');
					echo '<p class="gp_warning">';
					echo $langmessage['error_unpacking'];
					echo '</p>';
					return false;
*/
				}

				$filename = substr($filename,$archive_root_len);
			}


			$filename = '/'.trim($filename,'/');
			$full_path = $dir.'/'.$filename;

			if( $file_info['folder'] ){
				$folder = $full_path;
			}else{
				$folder = dirname($full_path);
			}

			if( !gpFiles::CheckDir($folder) ){
				echo '<p class="gp_warning">';
				echo sprintf($langmessage['COULD_NOT_SAVE'],$folder);
				echo '</p>';
				return false;
			}
			if( $file_info['folder'] ){
				continue;
			}
			if( !gpFiles::Save($full_path,$file_info['content']) ){
				echo '<p class="gp_warning">';
				echo sprintf($langmessage['COULD_NOT_SAVE'],$full_path);
				echo '</p>';
				return false;
			}
		}
		return true;
	}




	/*
	 *
	 *
	 *
	 */

	function GetInstalledComponents($from,$addon){
		$result = array();
		if( !is_array($from) ){
			return $result;
		}

		foreach($from as $name => $info){
			if( !isset($info['addon']) ){
				continue;
			}
			if( $info['addon'] !== $addon ){
				continue;
			}
			$result[] = $name;
		}
		return $result;
	}

	function CleanInstallFolder(){

		$addoncode = $this->addon_folder;
		$folders = gpFiles::readDir($addoncode,1);

		foreach($folders as $folder){
			if( !isset($this->config[$folder]) ){
				$full_path = $addoncode.'/'.$folder;
				$this->RmDir($full_path);
			}
		}
	}

	function RmDir($dir){

		if( is_link($dir) ){
			return unlink($dir);
		}

		$dh = @opendir($dir);
		if( !$dh ){
			return false;
		}
		$success = true;

		$subDirs = array();
		while( ($file = readdir($dh)) !== false){
			if( $file == '.' || $file == '..' ){
				continue;
			}
			$full_path = $dir.'/'.$file;
			if( is_dir($full_path) ){
				$subDirs[] = $full_path;
				continue;
			}
			if( !unlink($full_path) ){
				$success = false;
			}
		}
		closedir($dh);

		foreach($subDirs as $subDir){
			if( !$this->RmDir($subDir) ){
				$success = false;
			}
		}

		if( $success ){
			return gpFiles::RmDir($dir);
		}
		return false;
	}





}
