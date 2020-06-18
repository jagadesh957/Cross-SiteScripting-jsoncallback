<?php
defined('is_running') or die('Not an entry point...');

//sleep(3); //for testing

class gpAjax{

	function ReplaceContent($id,$content){
		gpAjax::JavascriptCall('WBx.response','replace',$id,$content);
	}

	function JavascriptCall(){
		$args = func_get_args();
		if( !isset($args[0]) ){
			return;
		}

		echo array_shift($args);
		echo '(';
		$comma = '';
		foreach($args as $arg){
			echo $comma;
			echo gpAjax::quote($arg);
			$comma = ',';
		}
		echo ');';
	}

	function quote(&$content){
		static $search = array('\\','"',"\n","\r",'<script','</script>');
		static $repl = array('\\\\','\"','\n','\r','<"+"script','<"+"/script>');

		return '"'.str_replace($search,$repl,$content).'"';
	}

	function JsonEval($content){
		echo '{DO:"eval"';
		echo ',CONTENT:';
		echo gpAjax::quote($content);
		echo '},';
	}

	function JsonDo($do,$selector,&$content){
		static $comma = '';
		echo $comma;
		echo '{DO:';
		echo gpAjax::quote($do);
		echo ',SELECTOR:';
		echo gpAjax::quote($selector);
		echo ',CONTENT:';
		echo gpAjax::quote($content);
		echo '}';
		$comma = ',';
	}


	/**
	 * Handle HTTP responses made with $_REQUEST['req'] = json (when <a ... name="gpajax">)
	 * Sends JSON object to client
	 *
	 */
	function Response(){
		global $page, $GP_GETALLGADGETS;

		if( !is_array($page->ajaxReplace) ){
			die();
		}

		//gadgets may be using gpajax/json request/responses
		gpOutput::TemplateSettings();
		gpOutput::PrepGadgetContent();


		echo $_REQUEST['jsoncallback'];
		echo '([';

		foreach($page->ajaxReplace as $arguments){

			if( is_array($arguments) ){
				$arguments += array(0=>'',1=>'',2=>'');
				gpAjax::JsonDo($arguments[0],$arguments[1],$arguments[2]);
			}else{
				switch( $arguments ){
					case '#gpx_content':
						$replace_id = '#gpx_content';

						if( isset($_GET['gpreqarea']) ){
							$replace_id = '#'.$_GET['gpreqarea'];
						}

						ob_start();
						$page->GetGpxContent();
						$content = ob_get_clean();
						gpAjax::JsonDo('replace',$replace_id,$content);
					break;
				}
			}
		}

		//always send messages
		ob_start();
		GetMessages();
		$content = ob_get_clean();
		if( !empty($content) ){
			gpAjax::JsonDo('messages','',$content);
		}

		echo ']);';
		die();
	}

	function InlineEdit($section_data){
		global $dataDir;

		$section_data += array('type'=>'','content'=>'');

		header('Content-type: application/x-javascript');

		$type = $section_data['type'];

		$scripts = array();
		$scripts[] = '/include/js/inline_edit/inline_editing.js';
		$scripts[] = '/include/thirdparty/jquery_ui/jquery-ui-1.8.7.custom.min.js';


		$type = 'text';
		if( !empty($section_data['type']) ){
			$type = $section_data['type'];
		}
		switch($section_data['type']){

			case 'gallery':
				$scripts = gpAjax::InlineEdit_Gallery($scripts);
			break;

			case 'include':
				$scripts = gpAjax::InlineEdit_Include($scripts);
			break;

			case 'text';
				$scripts = gpAjax::InlineEdit_Text($scripts);
			break;
		}

		$scripts = gpPlugin::Filter('InlineEdit_Scripts',array($scripts,$type));

		//send all scripts
		foreach($scripts as $script){
			$full_path = $dataDir.$script;
			echo ';';
			//echo "\n/**\n* $script\n*\n*/\n";
			readfile($full_path);
		}


		//create the section object that will be passed to gp_init_inline_edit
		// {key1:"value1",key2:"value2"}
		$section_object = '{';
		$comma = '';
		foreach($section_data as $key => $value){
			if( !ctype_alnum($key) ){
				continue;
			}
			$section_object .= $comma;
			$section_object .= $key;
			$section_object .= ':';
			$section_object .= gpAjax::quote($value);

			$comma = ',';
		}
		$section_object .= '}';


		//send call to gp_init_inline_edit()
		echo ';if( typeof(gp_init_inline_edit) == "function" ){';
		echo 'gp_init_inline_edit(';
		echo gpAjax::quote($_GET['area_id']);
		echo ',';
		echo $section_object;
		echo ');';
		echo '}else{alert("gp_init_inline_edit() is not defined");}';
	}

	function InlineEdit_Text($scripts){

		//autocomplete
		echo common::AutoCompleteValues(true);

		//ckeditor basepath and configuration
		$ckeditor_basepath = common::GetDir('/include/thirdparty/ckeditor_34/');
		echo 'CKEDITOR_BASEPATH = '.gpAjax::quote($ckeditor_basepath).';';
		echo 'var gp_ckconfig = {};';

		//gp_ckconfig options
		$options = array();
		echo common::CKConfig($options,'gp_ckconfig');

		$scripts[] = '/include/thirdparty/ckeditor_34/ckeditor.js';
		$scripts[] = '/include/js/inline_edit/inlineck.js';

		return $scripts;
	}

	function InlineEdit_Include($scripts){
		$scripts[] = '/include/js/inline_edit/include_edit.js';
		return $scripts;
	}

	function InlineEdit_Gallery($scripts){
		$scripts[] = '/include/js/inline_edit/gallery_edit_202.js';
		$scripts[] = '/include/js/jquery.auto_upload.js';
		return $scripts;
	}

}


