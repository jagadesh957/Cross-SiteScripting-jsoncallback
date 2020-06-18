<?php
defined('is_running') or die('Not an entry point...');


class special_display extends display{
	var $pagetype = 'special_display';
	var $requested = false;

	var $editable_content = false;
	var $editable_details = false; //true; //could be true

	function special_display($title){
		global $langmessage,$config;

		$this->requested = $title;
		$this->title = $title;
		$this->label = 'Special';
	}

	function RunScript(){
		global $gp_index;

		$scriptinfo = special_display::GetScriptInfo($this->requested);
		if( $scriptinfo === false ){

			switch($this->requested){
				case 'Special_ExtraJS';
					$this->ExtraJS();
				//dies
			}


			$this->Error_404($this->title);
			return;
		}

		$this->gp_index = $gp_index[$this->requested];
		$this->label = common::GetLabel($this->requested);
		$this->TitleInfo = $scriptinfo;

		$this->contentBuffer = special_display::ExecInfo($scriptinfo);
	}


	function GetScriptInfo($requested){
		global $dataDir,$gp_index,$gp_titles;


		$scripts['Special_Site_Map']['script'] = '/include/special/special_map.php';
		$scripts['Special_Site_Map']['class'] = 'special_map';

		$scripts['Special_Galleries']['script'] = '/include/special/special_galleries.php';
		$scripts['Special_Galleries']['class'] = 'special_galleries';

		$scripts['Special_Contact']['script'] = '/include/special/special_contact.php';
		$scripts['Special_Contact']['class'] = 'special_contact';

		$scripts['Special_Missing']['script'] = '/include/special/special_missing.php';
		$scripts['Special_Missing']['class'] = 'special_missing';



		$scriptinfo = false;
		if( isset($scripts[$requested]) ){

			$scriptinfo = $scripts[$requested];

		}elseif( isset($gp_index[$requested]) ){

			$index = $gp_index[$requested];

			$scriptinfo = $gp_titles[$index];

		}

/*
		if( isset($scriptinfo['script']) && !file_exists($dataDir.$scriptinfo['script']) ){
			$scriptinfo = false;
		}
*/

		return $scriptinfo;
	}


	function ExecInfo($scriptinfo){
		global $dataDir;

		ob_start();

		if( isset($scriptinfo['addon']) ){
			gpPlugin::SetDataFolder($scriptinfo['addon']);
		}

		if( isset($scriptinfo['script']) ){
			require($dataDir.$scriptinfo['script']);
		}
		if( isset($scriptinfo['class']) ){
			new $scriptinfo['class'](); //not passing any args to class, this is being used by special_missing.php
		}
		gpPlugin::ClearDataFolder();

		return ob_get_clean();
	}


	function ExtraJS(){
		header('Content-type: application/javascript');

		$_GET += array('which'=>array());

		foreach((array)$_GET['which'] as $which_code){

			switch($which_code){

				case 'autocomplete2':
					$options['admin_vals'] = false;
					$options['var_name'] = 'gp_include_titles';
					echo common::AutoCompleteValues(false,$options);
				break;

				case 'autocomplete':
					echo common::AutoCompleteValues(true);
				break;

				case 'gp_ckconfig':
					$options = array();
					echo common::CKConfig($options,'gp_ckconfig');
				break;
			}
		}

		die();
	}

}
