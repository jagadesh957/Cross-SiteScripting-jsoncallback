<?php
defined('is_running') or die('Not an entry point...');

error_reporting(E_ALL);
set_error_handler('showError');

//mod_rewrite settings
if( !isset($_SERVER['gp_rewrite']) ){
	if( defined('gp_indexphp') && (gp_indexphp === false) ){
		$_SERVER['gp_rewrite'] = true;
	}else{
		$_SERVER['gp_rewrite'] = false;
	}
}else{
	$_SERVER['gp_rewrite'] = true;
}


/**
 * See gpconfig.php for these configuration options
 */
defined('gpdebug') or define('gpdebug',false);
defined('gpdebugjs') or define('gpdebugjs',false);
defined('gptesting') or define('gptesting',false);
defined('gp_cookie_cmd') or define('gp_cookie_cmd',true);
defined('gp_browser_auth') or define('gp_browser_auth',false);
defined('gp_require_encrypt') or define('gp_require_encrypt',false);
defined('gp_chmod_file') or define('gp_chmod_file',0666);
defined('gp_chmod_dir') or define('gp_chmod_dir',0755);



@ini_set( 'session.use_only_cookies', '1' );
@ini_set( 'default_charset', 'utf-8' );


//see /var/www/others/mediawiki-1.15.0/languages/Names.php
$languages = array();
//$languages['ar'] = 'العربية';
$languages['bg']	= 'Български';		# Bulgarian
$languages['ca']	= 'Català';
$languages['cs']	= 'Česky';			# Czech
$languages['da']	= 'Dansk';
$languages['de']	= 'Deutsch';
$languages['el']	= 'Ελληνικά';		# Greek
$languages['en']	= 'English';
$languages['es']	= 'Español';
$languages['fi']	= 'Suomi';			# Finnish
$languages['fr']	= 'Français';
//$languages['gl']	= 'Galego';			# Galician
$languages['hu']	= 'Magyar';			# Hungarian
$languages['it']	= 'Italiano';
$languages['ja']	= '日本語';			# Japanese
$languages['lt']	= 'Lietuvių';		# Lithuanian
$languages['nl']	= 'Nederlands';		# Dutch
$languages['no']	= 'Norsk';			# Norwegian
$languages['pl']	= 'Polski';			# Polish
$languages['pt']	= 'Português';
$languages['pt-br']	= 'Português do Brasil';
$languages['ru']	= 'Русский';		# Russian
$languages['sk']	= 'Slovenčina';		# Slovak
$languages['sl']	= 'Slovenščina';	# Slovenian
$languages['sv']	= 'Svenska';		# Swedish
$languages['tr']	= 'Türkçe';			# Turkish
$languages['uk']	= 'Українська';		# Ukrainian
$languages['zh']	= '中文';			# (Zhōng Wén) - Chinese



$gpversion = '2.3.3';
$addonDataFolder = false;//deprecated
$addonCodeFolder = false;//deprecated
$addonPathData = false;
$addonPathCode = false;
$addonBrowsePath = 'http://gpeasy.com/index.php';
//$addonBrowsePath = 'http://gpeasy.loc/rocky/index.php';
//message('local browse path');
$checkFileIndex = true;
$wbErrorBuffer = array();
$gp_not_writable = array();


if( !defined('E_STRICT')){
	define('E_STRICT',2048);
}
if( !defined('E_RECOVERABLE_ERROR')){
	define('E_RECOVERABLE_ERROR',4096);
}
if( !defined('E_DEPRECATED') ){
	define('E_DEPRECATED',8192);
}
if( !defined('E_USER_DEPRECATED') ){
	define('E_USER_DEPRECATED',16384);
}



/* from wordpress
 * wp-settings.php
 * see also classes.php
 */
// Fix for IIS, which doesn't set REQUEST_URI
if ( empty( $_SERVER['REQUEST_URI'] ) ) {

	// IIS Mod-Rewrite
	if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
	}

	// IIS Isapi_Rewrite
	else if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];

	}else{

		// Use ORIG_PATH_INFO if there is no PATH_INFO
		if ( !isset($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO']) ){
			$_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
		}


		// Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
		if ( isset($_SERVER['PATH_INFO']) ) {
			if( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'] ){
				$_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
			}else{
				$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
			}
		}

		// Append the query string if it exists and isn't null
		if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
			$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
		}
	}
}

// Set default timezone in PHP 5.
if ( function_exists( 'date_default_timezone_set' ) )
	date_default_timezone_set( 'UTC' );


/**
 * Error Handling
 * Display the error and a debug_backtrace if gpdebug is not false
 * If gpdebug is an email address, send the error message to the address
 *
 */
function showError($errno, $errmsg, $filename, $linenum, $vars){
	global $wbErrorBuffer, $addon_current_id, $page;

	// since we're supporting php 4.3+ there are technically a lot on non-static functions being called statically
	if( $errno === E_STRICT ){
		return;
	}

	// for functions prepended with @ symbol to suppress errors
	if( ($errno === 0) || (error_reporting() === 0) ){
		return;
	}

	if( gpdebug === false ){

		//only record the error once per request
		$uniq = $filename.$linenum;
		if( !isset($wbErrorBuffer[$uniq]) ){

			$i = count($wbErrorBuffer);
			$args['en'.$i] = $errno;
			$args['el'.$i] = $linenum;
			$args['em'.$i] = substr($errmsg,0,255);
			$args['ef'.$i] = $filename; //filename length checked later
			if( isset($addon_current_id) ){
				$args['ea'.$i] = $addon_current_id;
			}
			if( is_object($page) && !empty($page->title) ){
				$args['ep'.$i] = $page->title;
			}
			$wbErrorBuffer[$uniq] = $args;
		}
		return;
	}

	$errortype = array (
				E_ERROR				=> "Error",
				E_WARNING			=> "Warning",
				E_PARSE				=> "Parsing Error",
				E_NOTICE 			=> "Notice",
				E_CORE_ERROR		=> "Core Error",
				E_CORE_WARNING 		=> "Core Warning",
				E_COMPILE_ERROR		=> "Compile Error",
				E_COMPILE_WARNING 	=> "Compile Warning",
				E_USER_ERROR		=> "User Error",
				E_USER_WARNING 		=> "User Warning",
				E_USER_NOTICE		=> "User Notice",
				E_STRICT			=> "Runtime Notice",
				E_RECOVERABLE_ERROR => 'Recoverable Error',
				E_DEPRECATED		=> "Deprecated",
				E_USER_DEPRECATED	=> "User Deprecated",
			 );

	$mess = '';
	$mess .= '<fieldset style="padding:1em">';
	$mess .= '<legend>'.$errortype[$errno].' ('.$errno.')</legend> '.$errmsg;
	$mess .= '<br/> &nbsp; &nbsp; <b>in:</b> '.$filename;
	$mess .= '<br/> &nbsp; &nbsp; <b>on line:</b> '.$linenum;
	if( isset($_SERVER['REQUEST_URI']) ){
		$mess .= '<br/> &nbsp; &nbsp; <b>Request:</b> '.$_SERVER['REQUEST_URI'];
	}
	if( isset($_SERVER['REQUEST_METHOD']) ){
		$mess .= '<br/> &nbsp; &nbsp; <b>Method:</b> '.$_SERVER['REQUEST_METHOD'];
	}


	//mysql.. for some addons
	if( function_exists('mysql_errno') && mysql_errno() ){
		$mess .= '<br/> &nbsp; &nbsp; Mysql Error ('.mysql_errno().')'. mysql_error();
	}

	//backtrace
	if( ($errno !== E_NOTICE) && ($errno != E_STRICT) && function_exists('debug_backtrace') ){
		$mess .= '<div><a href="javascript:void(0)" onclick="this.nextSibling.style.display=\'block\';;return false;">Show Backtrace</a>';
		$mess .= '<div style="display:none">';

		$temp = debug_backtrace(); //php 4.3+
		@array_shift($temp); //showError()
		$mess .= showArray($temp);

		$mess .= '</div>';
		$mess .= '</div>';
	}
	$mess .= '</p>';
	$mess .= '</fieldset>';

	if( gpdebug === true ){
		message($mess);
	}else{
		global $gp_mailer;
		includeFile('tool/email_mailer.php');
		$gp_mailer->SendEmail(gpdebug, 'debug ', $mess);
	}
}


/**
 * Calculate the difference between two micro times
 *
 */
function microtime_diff($a, $b, $eff = 6) {
	$a = array_sum(explode(" ", $a));
	$b = array_sum(explode(" ", $b));
	return sprintf('%0.'.$eff.'f', $b-$a);
}





/**
 * Fix GPCR if magic_quotes_gpc is on
 * magic_quotes_gpc is deprecated, but still on by default in many versions of php
 *
 */
if ( function_exists( 'get_magic_quotes_gpc' ) && get_magic_quotes_gpc() ) {
	fix_magic_quotes( $_GET );
	fix_magic_quotes( $_POST );
	fix_magic_quotes( $_COOKIE );
	fix_magic_quotes( $_REQUEST );

	//In version 4, $_ENV was also quoted
	//fix_magic_quotes( $_ENV ); //use GETENV() instead of $_ENV

	//doing this can break the application, the $_SERVER variable is not affected by magic_quotes
	//fix_magic_quotes( $_SERVER );
}

//If Register Globals
if( ini_get('register_globals') ){
	foreach($_REQUEST as $key => $value){
		$key = strtolower($key);
		if( ($key == 'globals') || $key == '_post'){
			die('Hack attempted.');
		}
	}
}


function fix_magic_quotes( &$arr ) {
	$new = array();
	foreach( $arr as $key => $val ) {
		$key = stripslashes($key);

		if( is_array( $val ) ){
			fix_magic_quotes( $val );
		}else{
			$val = stripslashes( $val );
		}
		$new[$key] = $val;
	}
	$arr = $new;
}

/**
 * Store a user message in the buffer
 *
 */
function message(){
	global $wbMessageBuffer;
	$wbMessageBuffer[] = func_get_args();
}

/**
 * Output the message buffer
 *
 */
function GetMessages(){
	global $wbMessageBuffer,$gp_not_writable,$langmessage;

	if( common::loggedIn() && count($gp_not_writable) > 0 ){
		$files = '<ul><li>'.implode('</li><li>',$gp_not_writable).'</li></ul>';
		$message = sprintf($langmessage['not_writable'],common::GetUrl('Admin_Status')).$files;
		message($message);
	}


	if( !empty($wbMessageBuffer) ){
		$result = '';
		foreach($wbMessageBuffer as $args){
			if( !isset($args[0]) ){
				continue;
			}

			if( isset($args[1]) ){
				$result .= '<li>'.call_user_func_array('sprintf',$args).'</li>';
			}else{
				$result .= '<li>'.$args[0].'</li>';
			}
		}
		//$result = str_replace('%s',' ',$result);


		$wbMessageBuffer = array();
		echo '<div class="messages"><div>';
		echo '<a style="" href="#" class="req_script close_message" name="close_message"></a>';
		echo '<ul>'.$result.'</ul></div></div>';
	}

	common::ErrorBuffer();
}


/**
 * Include a file relative to the include directory of the current installation
 *
 */
function includeFile( $file){
	global $dataDir;
	require_once( $dataDir.'/include/'.$file );
}


if( !function_exists('array_combine') ){
	function array_combine($keys, $values) {

	    $out = array();

	    $keys = array_values($keys);
	    $values = array_values($values);

	    foreach($keys as $key1 => $value1) {
	        $out[(string)$value1] = $values[$key1];
	    }

	    return $out;
	}
}


if( !function_exists('http_build_query') ){
	function http_build_query($data, $prefix='', $sep='', $key='') {
		$ret = array();
		foreach((array)$data as $k => $v) {

			if (is_int($k) && $prefix != null) {
				$k = urlencode($prefix . $k);
			}
			if((!empty($key)) || ($key === 0))  $k = $key.'['.urlencode($k).']';

			if (is_array($v) || is_object($v)) {
				array_push($ret, http_build_query($v, '', $sep, $k));
			} else {
				array_push($ret, $k.'='.urlencode($v));
			}
		}
		if (empty($sep)) $sep = ini_get('arg_separator.output');
		return implode($sep, $ret);
	}
}

function showArray($array){
	if( is_object($array) ){
		$array = get_object_vars($array);
	}

	$text = array();
	$text[] = '<table cellspacing="0" cellpadding="7" class="tableRows" border="0">';
	if(is_array($array)){
		$odd = null;
		$odd2 = null;

		foreach($array as $key => $value){

			if($odd2==1){
				$odd = 'bgcolor="white"';
				//$odd = ' class="tableRowEven" ';
				$odd2 = 2;
			}else{
				$odd = 'bgcolor="#ddddee"';
				//$odd = ' class="tableRowOdd" ';
				$odd2 = 1;
			}
			$text[] = '<tr '.$odd.'><td>';
 			$text[] = $key;
			$text[] = "</td><td>";
			if( is_bool($value) ){
				if($value){
					$text[]= '<tt>TRUE</tt>';
				}else{
					$text[] = '<tt>FALSE</tt>';
				}
			}elseif( is_numeric($value) ){
				$text[] = $value;
			}elseif( !empty($value) ){

				if( is_object($value) || is_array($value) ){
					$text[] = showArray($value);
				}elseif(is_string($value) ){
					$text[] = htmlspecialchars($value);
				}else{
					$text[] = '<b>--unknown value--:</b> '.gettype($value);
				}
			}
			$text[] = "</td></tr>";
		}
	}else{
		$text[] = '<tr><td>'.$array.'</td></tr>';
	}
	$text[] = "</table>";

	return "\n".implode("\n",$text)."\n";
}


/**
 *	Objects of this class handle the display of standard gpEasy pages
 *  The classes for admin pages and special pages extend the display class
 *
 */
class display{
	var $pagetype = 'display';
	var $gp_index;
	var $title;
	var $label;
	var $file = false;
	var $contentBuffer;
	var $TitleInfo;
	var $fileType = '';
	var $ajaxReplace = array('#gpx_content');
	var $admin_links = array();
	var $fileModTime = 0;

	//layout & theme
	var $theme_name = false;
	var $theme_color = false;
	var $theme_is_addon = false;
	var $get_theme_css = true;
	var $theme_dir;
	var $gpLayout;


	//<head> content
	var $head = '';
	var $head_js = array();
	var $head_script = '';
	var $jQueryCode = false;
	var $admin_js = false;
	var $head_force_inline = false;

	//css arrays
	var $css_user = array();
	var $css_admin = array();


	var $editable_content = true;
	var $editable_details = true;

	function display($title){
		$this->title = $title;
	}


	/**
	 * Get page content or do redirect for non-existant titles
	 * see special_missing.php and admin_missing.php
	 */
	function Error_404($requested){
		includeFile('special/special_missing.php');
		ob_start();
		new special_missing($requested);
		$this->contentBuffer = ob_get_clean();
	}

	function SetVars(){
		global $gp_index, $dataDir, $gp_titles;

		if( !isset($gp_index[$this->title]) ){
			$this->Error_404($this->title);
			return false;
		}

		$this->gp_index = $gp_index[$this->title];
		$this->TitleInfo = $gp_titles[$this->gp_index];
		$this->label =& $this->TitleInfo['label'];
		$this->file = gpFiles::PageFile($this->title);

		return true;
	}


	function RunScript(){
		global $langmessage, $dataDir;

		if( !$this->SetVars() ){
			return;
		}

		$this->GetFile();
		$this->contentBuffer = $this->SectionsToContent($this->file_sections);
	}

	/**
	 * Retreive the data file for the current title and update the data if necessary
	 *
	 */
	function GetFile(){

		$fileModTime = false;
		$file_sections = array();
		$meta_data = array();

		ob_start();
		if( file_exists($this->file) ){
			require($this->file);
		}
		$content = ob_get_clean();

		//update page to 2.0 if it wasn't done in upgrade.php
		if( !empty($content) && count($file_sections) == 0 ){
			if( !empty($meta_data['file_type']) ){
				$file_type =& $meta_data['file_type'];
			}elseif( !isset($file_type) ){
				$file_type = 'text';
			}

			switch($file_type){
				case 'gallery':
					$meta_data['file_type'] = 'text,gallery';
					$file_sections[0]['type'] = 'text';
					$file_sections[0]['content'] = '<h2>'.common::GetLabel($this->title).'</h2>';
					$file_sections[1]['type'] = 'gallery';
					$file_sections[1]['content'] = $content;
				break;

				default:
					$file_sections[0]['type'] = 'text';
					$file_sections[0]['content'] = $content;
				break;
			}

		}

		//fix gallery pages that weren't updated correctly
		if( isset($fileVersion) && version_compare($fileVersion,'2.0','<=') ){
			foreach($file_sections as $section_index => $section_info){
				if( $section_info['type'] == 'text' && strpos($section_info['content'],'gp_gallery') !== false ){
					//check further
					$lower_content = strtolower($section_info['content']);
					if( strpos($lower_content,'<ul class="gp_gallery">') !== false
						|| strpos($lower_content,'<ul class=gp_gallery>') !== false ){
							$file_sections[$section_index]['type'] = 'gallery';
					}
				}
			}
		}


		if( count($file_sections) == 0 ){
			$file_sections[0]['type'] = 'text';
			$file_sections[0]['content'] = '<p>Oops, this page no longer has any content.</p>';
		}

		$this->file_sections = $file_sections;
		$this->meta_data = $meta_data;
		$this->fileModTime = $fileModTime;

	}

	/**
	 * Loop through all $sections and collect the fomatted content
	 * @return string
	 *
	 */
	function SectionsToContent(&$sections){

		$content = '';
		foreach($sections as $section_num => $section_data){
			$content .= '<div class="GPAREA filetype-'.$section_data['type'].'">';
			$content .= $this->SectionToContent($section_data);
			$content .= '<div class="gpclear"></div>';
			$content .= '</div>';
		}
		return $content;
	}

	/**
	 * Return formatted content for the $section_data
	 * @return string
	 *
	 */
	function SectionToContent(&$section_data){

		$section_data = gpPlugin::Filter('SectionToContent',array($section_data));

		switch($section_data['type']){
			case 'text':
				return $this->TextContent($section_data['content']);

			case 'include':
				return $this->IncludeContent($section_data);

			case 'gallery':
				common::ShowingGallery();
				return $section_data['content'];
			default:
				return $section_data['content'];
		}

	}


	/**
	 * Replace gpEasy conent variables in $content
	 *
	 */
	function TextContent(&$content){


		//variables
		$vars['dirPrefix'] = $GLOBALS['dirPrefix'];
		$vars['linkPrefix'] = $GLOBALS['linkPrefix'];
		$vars['fileModTime'] = $this->fileModTime;
		$vars['title'] = $this->title;
		$vars['label'] = $this->label;

		$offset = 0;
		$continue = true;
		$i = 0;
		do{
			$i++;

			$pos = strpos($content,'$',$offset);
			if( $pos === false ){
				break;
			}

			//escaped?
			if( $pos > 0 ){
				$prev_char = $content{$pos-1};
				if( $prev_char == '\\' ){
					$offset = $pos+1;
					continue;
				}
			}

			$len = strspn($content,'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',$pos+1);
			if( $len == 0 ){
				$offset = $pos+1;
				continue;
			}

			$var = substr($content,$pos+1,$len);
			if( isset($vars[$var]) ){
				$content = substr_replace($content,$vars[$var],$pos,$len+1);
			}

			$offset = $pos+$len;

		}while(true);



		/* Testing old includes system ... this breaks editing */
		$this->ReplaceContent($content);

		return $content;
	}

	function ReplaceContent(&$content,$offset=0){
		global $dataDir,$gp_index;
		static $includes = 0;

		//prevent too many inlcusions
		if( $includes >= 10 ){
			return;
		}

		$pos = strpos($content,'{{',$offset);
		if( $pos === false ){
			return;
		}
		$pos2 = strpos($content,'}}',$pos);
		if( $pos2 === false ){
			return;
		}

		$arg = substr($content,$pos+2,$pos2-$pos-2);
		$title = gpFiles::CleanTitle($arg);
		if( !isset($gp_index[$title]) ){
			$this->ReplaceContent($content,$pos2);
			return;
		}


		$file = gpFiles::PageFile($title);
		if( !file_exists($file) ){
			$this->ReplaceContent($content,$pos2);
			return;
		}

		$includes++;
		$file_sections = array();
		ob_start();
		require($file);
		ob_get_clean();

		$replacement = '';
		foreach($file_sections as $section_num => $section_data){
			$replacement .= '<div class="gpinclude" title="'.$title.'" >'; //contentEditable="false"
			$replacement .= $this->SectionToContent($section_data);
			$replacement .= '</div>';
		}

		//is {{...}} wrapped by <p>..</p>?
		$pos3 = strpos($content,'</p>',$pos2);
		if( $pos3 > 0 ){
			$pieceAfter = substr($content,$pos2,($pos3-$pos2));
			if( strpos($pieceAfter,'<') == false ){
				$replacement = "</p>\n".$replacement."\n<p>";
			}
		}

		//$replacement = "\n<!-- replacement -->\n".$replacement."\n<!-- end replacement -->\n";


		$content = substr_replace($content,$replacement,$pos,$pos2-$pos+2);
		$this->ReplaceContent($content,$pos);
	}

	function IncludeContent($data){
		global $dataDir, $langmessage, $gp_index;

		if( isset($data['index']) ){
			$requested = common::IndexToTitle($data['index']);
		}else{
			$requested = $data['content'];
		}


		if( empty($requested) ){
			return '<p>'.$langmessage['File Include'].'</p>';
		}

		$cleaned = gpFiles::CleanTitle($requested);
		if( isset($data['include_type']) ){
			$type = $data['include_type'];
		}else{
			$type = common::SpecialOrAdmin($cleaned);
		}
		switch($type){
			case 'gadget':
			return $this->IncludeGadget($requested,$cleaned);

			case 'special':
			return $this->IncludeSpecial($requested,$cleaned);

			default:
			return $this->IncludePage($requested,$cleaned);
		}
	}

	function IncludeGadget($requested,$cleaned){
		global $config;

		if( !isset($config['gadgets'][$cleaned]) ){
			return '{{Gadget Not Found: '.$requested.'}}';
		}

		ob_start();
		gpOutput::ExecInfo($config['gadgets'][$cleaned]);
		return ob_get_clean();
	}

	function IncludeSpecial($requested,$cleaned){
		global $langmessage;
		includeFile('special.php');

		$scriptinfo = special_display::GetScriptInfo($cleaned);

		if( $scriptinfo === false ){
			return '<p>'.$langmessage['File Include'].'</p>';
		}

		return special_display::ExecInfo($scriptinfo);
	}

	function IncludePage($requested,$cleaned){
		global $dataDir,$gp_index;

		if( !isset($gp_index[$cleaned]) ){
			return '{{'.$requested.'}}';
		}

		$file = gpFiles::PageFile($cleaned);
		if( !file_exists($file) ){
			return '{{'.$requested.'}}';
		}

		$file_sections = array();
		require($file);
		return $this->SectionsToContent($file_sections);
	}


	/**
	 * Set the page's theme name and path information according to the specified $layout
	 * If $layout is not found, use the installation's default theme
	 *
	 */
	function SetTheme($layout=false){
		global $gpLayouts, $dataDir;

		if( $layout === false ){
			$layout = display::OrConfig($this->gp_index,'gpLayout');
		}

		$layout_info = common::LayoutInfo($layout);
		if( !$layout_info ){
			$this->gpLayout = false;
			$this->theme_name = 'Light_Texture';
			$this->theme_color = 'Blue';
			$this->theme_is_addon = false;
			$this->theme_dir = $dataDir.'/themes/'.$this->theme_name;

		}else{
			$this->gpLayout = $layout;
			$this->theme_name = $layout_info['theme_name'];
			$this->theme_color = $layout_info['theme_color'];
			$this->theme_is_addon = $layout_info['is_addon'];
			$this->theme_dir = $layout_info['dir'];
		}

	}


	/**
	 * Return the most relevant configuration value for a configuration option ($var)
	 * Check configuration for a page ($id) first, then parent pages (determined by main menu), then the site $config
	 *
	 * @return mixed
	 *
	 */
	function OrConfig($id,$var){
		global $config, $gp_titles;

		if( $id ){
			if( !empty($gp_titles[$id][$var]) ){
				return $gp_titles[$id][$var];
			}

			if( display::ParentConfig($id,$var,$value) ){
				return $value;
			}
		}

		if( isset($config[$var]) ){
			return $config[$var];
		}

		return false;
	}

	/**
	 * Traverse the main menu upwards looking for a configuration setting for $var
	 * Start at the title represented by $checkId
	 * Set $value to the configuration setting if a parent page has the configuration setting
	 *
	 * @return bool
	 */
	function ParentConfig($checkId,$var,&$value){
		global $gp_menu, $gp_titles;

		//get configuration of parent titles
		if( !isset($gp_menu[$checkId]) || !isset($gp_menu[$checkId]['level']) ){
			return false;
		}

		$checkLevel = $gp_menu[$checkId]['level'];

		$menu_ids = array_keys($gp_menu);
		$key = array_search($checkId,$menu_ids);
		for($i = ($key-1); $i >= 0; $i--){
			$id = $menu_ids[$i];

			//check the level
			$level = $gp_menu[$id]['level'];
			if( $level >= $checkLevel ){
				continue;
			}
			$checkLevel = $level;

			if( !empty($gp_titles[$id][$var]) ){
				$value = $gp_titles[$id][$var];
				return true;
			}

			//no need to go further
			if( $level == 0 ){
				return false;
			}

		}
		return false;
	}


	/*
	 * Get functions
	 *
	 * Missing:
	 *		$#sitemap#$
	 * 		different menu output
	 *
	 */

	function GetSiteLabel(){
		global $config;
		echo $config['title'];
	}
	function GetSiteLabelLink(){
		global $config;
		echo common::Link('',$config['title']);
	}
	function GetPageLabel(){
		echo $this->label;
	}


	/* deprecated */
	function GetAllGadgets(){
		gpOutput::GetAllGadgets();
	}

	/* deprecated */
	function GetGadget(){}

	/* deprecated */
	function GetExpandMenu(){
		gpOutput::Get('ExpandMenu');
	}

	/* deprecated */
	function GetFullMenu(){
		gpOutput::Get('FullMenu');
	}
	/* deprecated */
	function GetMenu(){
		gpOutput::Get('Menu');
	}
	/* deprecated */
	function GetSubMenu(){
		gpOutput::Get('SubMenu');
	}
	/* deprecated */
	function GetExpandLastMenu(){
		gpOutput::Get('ExpandLastMenu');
	}
	/* deprecated */
	function GetTopTwoMenu(){
		gpOutput::Get('TopTwoMenu');
	}
	/* deprecated */
	function GetBottomTwoMenu(){
		gpOutput::Get('BottomTwoMenu');
	}

	/* deprecated */
	function GetFooter(){
		gpOutput::Get('Extra','Footer');
	}
	/* deprecated */
	function GetExtra($name='Side_Menu'){
		gpOutput::Get('Extra',$name);
	}
	/* deprecated */
	function GetAdminLink(){
		gpOutput::GetAdminLink();
	}

	/* deprecated */
	function GetHead() {
		gpOutput::GetHead();
	}

	/* deprecated */
	function GetLangText($key){
		gpOutput::Get('Text',$key);
	}

	function GetContent(){

		$this->GetGpxContent();

		echo '<div id="gpAfterContent">';
		gpOutput::Get('AfterContent');
		gpPlugin::Action('GetContent_After');
		echo '</div>';
	}

	function GetGpxContent(){
		$class = '';
		if( isset($this->meta_data['file_number']) ){
			$class = 'filenum-'.$this->meta_data['file_number'];
		}

		echo '<div id="gpx_content" class="'.$class.' cf">';

		echo $this->contentBuffer;


		echo '</div>';
	}

}





class common{

	function RunOut(){
		global $page;

		//$page->SetTheme();
		$page->RunScript();

		//decide how to send the content
		gpOutput::Prep();
		$req = '';
		if( isset($_REQUEST['gpreq']) ){
			//sleep(3); //for testing
			$req = $_REQUEST['gpreq'];
		}
		switch($req){

			// <a name="admin_box">
			case 'flush':
				gpOutput::Flush();
			break;

			// remote request
			case 'body':
				common::CheckTheme();
				gpOutput::BodyAsHTML();
			break;

			// <a name="gpajax">
			case 'json':
				common::CheckTheme();
				includeFile('tool/ajax.php');
				gpAjax::Response();
			break;

			case 'content':
				gpOutput::Content();
			break;

			default:
				common::CheckTheme();
				gpOutput::Template();
			break;
		}


		/* attempt to send 304 response */
		if( $page->fileModTime == 0 ){
			return;
		}

		common::Send304( common::GenEtag( $page->fileModTime, ob_get_length() ) );
	}


	/**
	 * Send a 304 Not Modified Response to the client if HTTP_IF_NONE_MATCH matched $etag and headers have not already been sent
	 * Othewise, send the etag
	 * @param string $etag The calculated etag for the current page
	 *
	 */
	function Send304($etag){
		global $config;

		if( !$config['etag_headers'] ) return;

		if( headers_sent() ) return;

		//always send the etag
		header('ETag: "'.$etag.'"');

		if( empty($_SERVER['HTTP_IF_NONE_MATCH'])
			|| trim($_SERVER['HTTP_IF_NONE_MATCH'],'"') != $etag ){
				return;
		}

		//don't use ob_get_level() in while loop to prevent endless loops;
		$level = ob_get_level();
		while( $level > 0 ){
			@ob_end_clean();
			$level--;
		}

		// 304 should not have a response body or Content-Length header
		//header('Not Modified',true,304);
		common::status_header(304,'Not Modified');
		header('Connection: close');
		exit();
	}

	/**
	 * Set HTTP status header.
	 * Modified From Wordpress
	 *
	 * @since 2.3.3
	 * @uses apply_filters() Calls 'status_header' on status header string, HTTP
	 *		HTTP code, HTTP code description, and protocol string as separate
	 *		parameters.
	 *
	 * @param int $header HTTP status code
	 * @param string $text HTTP status
	 * @return unknown
	 */
	function status_header( $header, $text ) {
		$protocol = $_SERVER['SERVER_PROTOCOL'];
		if( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
			$protocol = 'HTTP/1.0';
		$status_header = "$protocol $header $text";
		return @header( $status_header, true, $header );
	}

	function GenEtag($modified,$content_length){
		return base_convert( $modified, 10, 36).'.'.base_convert( $content_length, 10, 36);
	}

	function CheckTheme(){
		global $page;
		if( $page->theme_name === false ){
			$page->SetTheme();
		}
	}

	/**
	 * Return an array of information about the layout
	 */
	function LayoutInfo($layout){
		global $gpLayouts,$dataDir;

		if( !isset($gpLayouts[$layout]) ){
			return false;
		}

		$layout_info = $gpLayouts[$layout];
		$theme = $layout_info['theme'];
		$layout_info['theme_name'] = dirname($theme);
		$layout_info['theme_color'] = basename($theme);

		//check the path
		if( isset($layout_info['is_addon']) && $layout_info['is_addon'] ){
			$layout_info['dir'] = $dataDir.'/data/_themes/'.$layout_info['theme_name'];
		}else{
			$layout_info['dir'] = $dataDir.'/themes/'.$layout_info['theme_name'];
			$layout_info['is_addon'] = false;
		}

		$layout_info['template'] = $layout_info['dir'].'/template.php';

		if( !file_exists($layout_info['template']) ){
			return false;
		}

		return $layout_info;

	}


	/*
	 *
	 *
	 * Entry Functions
	 *
	 *
	 */

	function EntryPoint($level=0,$expecting='index.php',$sessions=true){

		clearstatcache();
		if( !ini_get('zlib.output_compression') && 'ob_gzhandler' != ini_get('output_handler') ){
			ob_start( 'ob_gzhandler' );//available since 4.0.4
		}else{
			ob_start();
		}
		common::SetGlobalPaths($level,$expecting);
		common::SetLinkPrefix();
		common::RequestLevel();
		common::gpInstalled();
		common::GetConfig();
		if( $sessions ){
			common::sessions();
		}
		includeFile('tool/gpOutput.php');
		includeFile('tool/Plugins.php');
	}

	function gpInstalled(){
		global $dataDir;
		if( @file_exists($dataDir.'/data/_site/config.php') ){
			return;
		}

		if( file_exists($dataDir.'/include/install/install.php') ){
			includeFile('install/install.php');
			die();
		}

		die('<p>Sorry, this site is temporarily unavailable.</p>');
	}

	function SetGlobalPaths($DirectoriesAway,$expecting){
		global $dataDir, $dirPrefix, $rootDir, $linkPrefix;

		$rootDir = str_replace('\\','/',dirname(dirname(__FILE__)));

		// dataDir, make sure it contains $expecting. Some servers using cgi do not set this properly
		// required for the Multi-Site plugin
		$dataDir = common::GetEnv('SCRIPT_FILENAME',$expecting);
		if( $dataDir !== false ){
			$dataDir = common::ReduceGlobalPath($dataDir,$DirectoriesAway);
		}else{
			$dataDir = $rootDir;
		}


		// Not entirely secure: http://blog.php-security.org/archives/72-Open_basedir-confusion.html
		// Only allowed to tighten open_basedir in php 5.3+
		// removing in gpeasy 2.2: not all php versions handle it the same way, some don't allow access to subdirectories
		//if( defined('multi_site_unique') ){
			//include directory and $dataDir
			//$path = $dataDir.'/.'.PATH_SEPARATOR.$dataDir.'/include'.PATH_SEPARATOR.$dataDir.'/themes'.PATH_SEPARATOR.$dataDir.'/addons';
			//@ini_set('open_basedir',$path);
		//}


		//$dirPrefix
		$dirPrefix = common::GetEnv('SCRIPT_NAME',$expecting);
		if( $dirPrefix === false ){
			$dirPrefix = common::GetEnv('PHP_SELF',$expecting);
		}

		//remove everything after $expecting, $dirPrefix can at times include the PATH_INFO
		$pos = strpos($dirPrefix,$expecting);
		$dirPrefix = substr($dirPrefix,0,$pos+strlen($expecting));

		$dirPrefix = common::ReduceGlobalPath($dirPrefix,$DirectoriesAway);
		if( $dirPrefix == '/' ){
			$dirPrefix = '';
		}

	}

	function SetLinkPrefix(){
		global $linkPrefix, $dirPrefix;

		if( !$_SERVER['gp_rewrite'] ){
			$linkPrefix = $dirPrefix.'/index.php';
		}else{
			$linkPrefix = $dirPrefix;
		}
	}

	//get the environment variable and make sure it contains $expecting
	function GetEnv($var,$expecting=false){
		$value = false;
		if( isset($_SERVER[$var]) ){
			$value = $_SERVER[$var];
		}else{
			$value = getenv($var);
		}
		if( $expecting && strpos($value,$expecting) === false ){
			return false;
		}
		return $value;
	}

	function ReduceGlobalPath($path,$DirectoriesAway){
		$path = dirname($path);

		$i = 0;
		while($i < $DirectoriesAway){
			$path = dirname($path);
			$i++;
		}
		return str_replace('\\','/',$path);
	}



	//use dirPrefix to find requested level
	function RequestLevel(){
		global $dirPrefixRel,$dirPrefix;

		$path = $_SERVER['REQUEST_URI'];

		//strip the query string.. in case it contains "/"
		$pos = strpos($path,'?');
		if( $pos > 0 ){
			$path =  substr($path,0,$pos);
		}

		//dirPrefix will be decoded
		$path = rawurldecode($path); //%20 ...

		if( !empty($dirPrefix) ){
			$pos = strpos($path,$dirPrefix);
			if( $pos !== false ){
				$path = substr($path,$pos+strlen($dirPrefix));
			}
		}

		$path = ltrim($path,'/');
		$count = substr_count($path,'/');
		if( $count == 0 ){
			$dirPrefixRel = '.';
		}else{
			$dirPrefixRel = str_repeat('../',$count);
			$dirPrefixRel = rtrim($dirPrefixRel,'/');//GetDir() arguments always start with /
		}
	}



	/*
	 *
	 * Link Functions
	 *
	 *
	 */
	function Ampersands($arg){

		if( strpos($arg,';') ){
			return $arg;
		}
		return str_replace('&','&amp;',$arg);
	}


	/* deprecated: Use common::Link() instead */
	function Link_Admin($href,$label,$query='',$attr=''){
		return common::Link($href,$label,$query,$attr);
	}

	function Link($href,$label,$query='',$attr='',$nonce_action=false){

		if( strpos($attr,'title="') === false){
			$attr .= ' title="'.common::Ampersands(strip_tags($label)).'" ';
		}

		return '<a href="'.common::GetUrl($href,$query,true,$nonce_action).'" '.$attr.'>'.common::Ampersands($label).'</a>';
	}


	function GetUrl($href,$query='',$ampersands=true,$nonce_action=false){
		global $linkPrefix, $config;

		if( isset($config['homepath']) && $href == $config['homepath'] ){
			$href = '';
		}

		if( $ampersands ){
			$href = common::Ampersands($href);
			$query = common::Ampersands($query);
		}

		if( !empty($query) ){
			$query = '?'.$query;
		}

		if( $nonce_action ){
			$nonce = common::new_nonce($nonce_action);
			if( empty($query) ){
				$query = '?';
			}else{
				$query .= '&amp;'; //in the cases where $ampersands is false, nonces are not used
			}
			$query .= '_gpnonce='.$nonce;
		}

		return $linkPrefix.'/'.$href.$query;
	}

	function AbsoluteLink($href,$label,$query='',$attr=''){

		if( strpos($attr,'title="') === false){
			$attr .= ' title="'.htmlspecialchars(strip_tags($label)).'" ';
		}

		return '<a href="'.common::AbsoluteUrl($href,$query).'" '.$attr.'>'.common::Ampersands($label).'</a>';
	}

	function AbsoluteUrl($href,$query='',$with_schema=true){

		if( isset($_SERVER['HTTP_HOST']) ){
			$server = $_SERVER['HTTP_HOST'];
		}else{
			$server = $_SERVER['SERVER_NAME'];
		}

		$schema = '';
		if( $with_schema ){
			$schema = ( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
		}

		return $schema.$server.common::GetUrl($href,$query);
	}


	function GetDir($dir){
		global $dirPrefix;
		return str_replace(' ','%20',$dirPrefix.$dir);

		/* Breaks different entry points.. see admin_menu.php
		global $dirPrefixRel;
		return $dirPrefixRel.str_replace(' ','%20',$dir);
		*/
	}

	function GetDir_Prefixed($dir){
		global $dirPrefix;
		return str_replace(' ','%20',$dirPrefix.$dir);
	}


	function GetLabelIndex($index,$amp=true){
		global $gp_titles,$langmessage;

		$info = $gp_titles[$index];

		if( isset($info['label']) ){
			$return = $info['label'];

		}elseif( isset($info['lang_index']) ){
			$return = $langmessage[$info['lang_index']];

		}else{
			$return = common::IndexToTitle($index);
			$return = gpFiles::CleanLabel($return);
		}
		if( $amp ){
			return str_replace('&','&amp;',$return);
		}
		return $return;
	}

	function GetLabel($title,$amp=true){
		global $gp_titles, $gp_index, $langmessage;

		$return = false;
		if( isset($gp_index[$title]) ){
			$id = $gp_index[$title];
			$info =& $gp_titles[$id];

			if( isset($info['label']) ){
				$return = $info['label'];

			}elseif( isset($info['lang_index']) ){

				$return = $langmessage[$info['lang_index']];
			}
		}

		if( $return === false ){
			$return = gpFiles::CleanLabel($title);
		}

		if( $amp ){
			return str_replace('&','&amp;',$return);
		}
		return $return;
	}



	/* deprecated */
	function UseFCK($contents,$name='gpcontent'){
		common::UseCK($contents,$name);
	}


	/* ckeditor 3.0
		- Does not have a file browser

		configuration options
		- http://docs.cksource.com/ckeditor_api/symbols/CKEDITOR.config.html
	*/
	function UseCK($contents,$name='gpcontent',$options=array()){
		global $config,$page;

		$options['rows'] = '20';
		$options['cols'] = '50';

		echo "\n\n";

		echo '<textarea name="'.$name.'" style="width:90%" rows="'.$options['rows'].'" cols="'.$options['cols'].'" class="CKEDITAREA">';
		echo htmlspecialchars($contents);
		echo '</textarea><br/>';


		//$page->head_js[] = '/include/thirdparty/ckeditor_34/ckeditor.js'; //wasn't working quite right, and it's called seperately for inline editing
		$page->head .= "\n".'<script type="text/javascript" src="'.common::GetDir('/include/thirdparty/ckeditor_34/ckeditor.js?3.6.2').'"></script>';


		common::PrepAutoComplete(false,true);

		ob_start();

		echo 'CKEDITOR.replaceAll( function(tarea,config){';

		echo 'if( tarea.className.indexOf("CKEDITAREA") == -1 ) return false;';

		echo common::CKConfig($options);

		echo 'return true;';
		echo '});';
		echo "\n\n";
		$page->jQueryCode .= ob_get_clean();

	}

	function CKConfig($options=array(),$config_name='config'){
		global $config;

		$defaults = array();
		$defaults['browser'] = true; //not actually a ckeditor configuration value, but we're keeping it now for reverse compat
		$defaults['smiley_path'] = common::GetDir('/include/thirdparty/ckeditor_34/plugins/smiley/images/');
		$defaults['customConfig'] = common::GetDir('/include/js/ckeditor_config.js');
		if( $config['langeditor'] == 'inherit' ){
			$defaults['language'] = $config['language'];
		}else{
			$defaults['language'] = $config['langeditor'];
		}

		$options += $defaults;

		$values = $config_name.' = (function(a){';

		//keep for reverse compat;
		if( isset($options['config_text']) ){
			$values .= $options['config_text'];
			unset($options['config_text']);
		}

		//browser paths
		if( $options['browser'] ){
			$values .= 'a.filebrowserBrowseUrl = "'.common::GetDir('/include/admin/admin_browser.html?type=all').'";';
			$values .= 'a.filebrowserImageBrowseUrl = "'.common::GetDir('/include/admin/admin_browser.html?dir=%2Fimage').'";';
			$values .= 'a.filebrowserFlashBrowseUrl = "'.common::GetDir('/include/admin/admin_browser.html?dir=%2Fflash').'";';
		}
		unset($options['browser']);

		foreach($options as $key => $value ){
			if( $value === true ){
				$values .= 'a.'.$key.'=true;';
			}elseif( $value === false){
				$values .= 'a.'.$key.'=false;';
			}elseif( $value === null ){
				$values .= 'a.'.$key.'=null;';
			}else{
				$values .= 'a.'.$key.'="'.$value.'";';
			}
		}

		$values .= ';return a;})('.$config_name.');';

		return $values;
	}

	function PrepAutoComplete($autocomplete_js=true,$GetUrl=true){
		global $page;

		common::LoadJqueryUI();

		if( $autocomplete_js ){
			$page->head_js[] = '/include/js/autocomplete.js';
		}

		$page->head_script .= common::AutoCompleteValues($GetUrl);
	}

	/**
	 * Add the jQuery UI javascript to the document
	 * Includes Autocomplete and Sortable
	 *
	 * @static
	 * @since 2.0b1
	 */
	function LoadjQueryUI(){
		global $page;

		$page->head_js[] = '/include/thirdparty/jquery_ui/jquery-ui-1.8.7.custom.min.js';
		$page->css_admin[] = '/include/thirdparty/jquery_ui/jquery-ui-1.8.7.custom.css';
	}

	/**
	 * Return javascript code to be used with autocomplete (jquery ui)
	 *
	 */
	function AutoCompleteValues($GetUrl=true,$options = array()){
		global $gp_index;

		$options += array(	'admin_vals' => true,
							'var_name' => 'gptitles'
							);


		//internal link array
		$code = 'var '.$options['var_name'].'=[';
		foreach($gp_index as $slug => $id){
			if( $GetUrl ){
				$code .= '["'.addslashes(common::GetLabel($slug)).'","'.addslashes(common::GetUrl($slug)).'"],';
			}else{
				$code .= '["'.addslashes(common::GetLabel($slug)).'","'.addslashes($slug).'"],';
			}
		}


		if( $options['admin_vals'] && class_exists('admin_tools') ){
			$scripts = admin_tools::AdminScripts();
			foreach($scripts as $url => $info){
				if( $GetUrl ){
					$url = common::GetUrl($url);
				}
				$code .= '["'.addslashes($info['label']).'","'.addslashes($url).'"],';
			}
		}

		$code .= '];';
		return $code;
	}


	/**
	 * Add gallery js and css to the <head> section of a page
	 *
	 */
	function ShowingGallery(){
		global $page;
		static $showing = false;
		if( $showing ) return;
		$showing = true;

		common::AddColorBox();
		$css = gpPlugin::OneFilter('Gallery_Style');
		if( $css === false  ){
			$page->css_user[] = '/include/css/default_gallery.css';
			return;
		}
		$page->head .= "\n".'<link type="text/css" media="screen" rel="stylesheet" href="'.$css.'" />';
	}

	/**
	 * Add js and css elements to the <head> section of a page
	 *
	 */
	function AddColorBox(){
		global $page,$config;
		static $init = false;

		if( $init ){
			return;
		}
		$init = true;

		$folder = 'colorbox136';
		$folder = 'colorbox139';
		$style = $config['colorbox_style']; //'example1';

		$page->admin_js = true;

		$page->css_user[] = '/include/thirdparty/'.$folder.'/'.$style.'/colorbox.css';
		$page->head_js[] = '/include/thirdparty/'.$folder.'/colorbox/jquery.colorbox.js';
	}

	/**
	 * Set the $config array from /data/_site/config.php
	 *
	 */
	function GetConfig(){
		global $config, $gp_index, $dataDir, $gp_menu;

		require($dataDir.'/data/_site/config.php');
		$GLOBALS['fileModTimes']['config.php'] = $fileModTime;
		common::GetPagesPHP();

		//remove old values
		if( isset($config['linkto']) ) unset($config['linkto']);
		if( isset($config['menu_levels']) ) unset($config['menu_levels']); //2.3.2

		//set values
		$config += common::ConfigDefaults();
		$config['dirPrefix'] = $GLOBALS['dirPrefix'];
		$homepath_key = key($gp_menu);
		$config['homepath'] = common::IndexToTitle($homepath_key);

		//message($config['homepath']);
		//gpFiles::PageFile($config['homepath']);

		defined('gp_session_cookie') or define('gp_session_cookie','gpEasy_'.substr(sha1($config['gpuniq']),12,12));

		//get language file
		common::GetLangFile();

		//upgrade?
		if( version_compare($config['gpversion'],'1.8a1','<') ){
			includeFile('tool/upgrade.php');
			new gpupgrade();
		}
	}

	/**
	 * Return an array with some of gpEasy's configuration defailts
	 *
	 */
	function ConfigDefaults(){
		return array(
				'maximgarea' => '691200',
				'check_uploads' => true,
				'shahash' => function_exists('sha1'), //1.6RC3
				'colorbox_style' => 'example1',
				'combinecss' => true,
				'combinejs' => true,
				'etag_headers' => true,
				'customlang' => array(),
				);
	}

	/**
	 * Set global variables ( $gp_index, $gp_titles, $gp_menu and $gpLayouts ) from _site/pages.php
	 *
	 */
	function GetPagesPHP(){
		global $gp_index, $gp_titles, $gp_menu, $dataDir, $gpLayouts;
		$gp_index = array();

		$pages = array();
		require($dataDir.'/data/_site/pages.php');
		$GLOBALS['fileModTimes']['pages.php'] = $fileModTime;
		$gpLayouts = $pages['gpLayouts'];


		//update for < 2.0a3
		if( isset($pages['gpmenu']) && isset($pages['gptitles']) ){

			//1.7b2
			if( !isset($pages['gptitles']['Special_Missing']) ){
				$pages['gptitles']['Special_Missing']['type'] = 'special';
				$pages['gptitles']['Special_Missing']['label'] = 'Missing';
			}

			foreach($pages['gptitles'] as $title => $info){
				$index = common::NewFileIndex();
				$gp_index[$title] = $index;
				$gp_titles[$index] = $info;
			}

			foreach($pages['gpmenu'] as $title => $level){
				$index = $gp_index[$title];
				$gp_menu[$index] = array('level' => $level);
			}
			return;
		}

		$gp_index = $pages['gp_index'];
		$gp_titles = $pages['gp_titles'];
		$gp_menu = $pages['gp_menu'];


		/**
		 * update special page indexes
		 * setup so special pages can be renamed
		 *
		if( version_compare($fileVersion,'2.3.3','<') ){
			$new_index = array();
			$new_titles = array();
			foreach($gp_index as $title => $index){
				$type = common::SpecialOrAdmin($title);
				$info =& $gp_titles[$index];

				if( $type == 'special' ){
					$index = $title;
					$info['type'] = 'special'; //some older versions didn't maintain this value well
				}
				$new_index[$title] = $index;
				$new_titles[$index] = $info;
			}
			$gp_index = $new_index;
			$gp_titles = $new_titles;
		}
		*/
	}

	/**
	 * Generate a new file index
	 * skip indexes that are just numeric
	 */
	function NewFileIndex(){
		global $gp_index, $gp_titles;

		$num_index = 0;

		/* prevent reusing old indexes */
		if( count($gp_index) > 0 ){
			end($gp_index);
			$last_index = current($gp_index);
			reset($gp_index);
			$num_index = base_convert($last_index,36,10);
			$num_index++;
		}

		do{
			$index = base_convert($num_index,10,36);
			$num_index++;
		}while( is_numeric($index) || isset($gp_titles[$index]) );

		return $index;
	}


	/**
	 * Return the title of file using the index
	 * @param string $index The index of the file
	 */
	function IndexToTitle($index){
		global $gp_index;
		return array_search($index,$gp_index);
	}


	/**
	 * Return the configuration value or default if it's not set
	 *
	 * @since 1.7
	 *
	 * @param string $key The key to the $config array
	 * @param mixed $default The value to return if $config[$key] is not set
	 * @return mixed
	 */
	function ConfigValue($key,$default=false){
		global $config;
		if( !isset($config[$key]) ){
			return $default;
		}
		return $config[$key];
	}

	/**
	 * Generate a random alphanumeric string of variable length
	 *
	 * @param int $len length of string to return
	 * @param bool $cases Whether or not to use upper and lowercase characters
	 */
	function RandomString($len = 40, $cases = true ){

		$string = 'abcdefghijklmnopqrstuvwxyz1234567890';
		if( $cases ){
			$string .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		}

		$string = str_repeat($string,round($len/2));
		$string = str_shuffle( $string );
		$start = mt_rand(1, (strlen($string)-$len));

		return substr($string,$start,$len);
	}

	/**
	 * Include the main.inc language file for $language
	 * Language files were renamed to main.inc for version 2.0.2
	 *
	 */
	function GetLangFile($file='main.inc',$language=false){
		global $dataDir, $config, $langmessage;

		if( $language === false ){
			$language = $config['language'];
		}


		$fullPath = $dataDir.'/include/languages/'.$language.'/main.inc';
		if( file_exists($fullPath) ){
			include($fullPath);
			return;
		}

		//try to get the english file
		$fullPath = $dataDir.'/include/languages/en/main.inc';
		if( file_exists($fullPath) ){
			include($fullPath);
		}

	}

	/**
	 * Determine if the $title is a special or admin page
	 * @param string $title
	 * @return mixed 'admin','special' or false
	 */
	function SpecialOrAdmin($title){
		global $gp_index,$gp_titles;

		$lower_title = strtolower($title);

		// Admin without the _ because of the primary Admin page at /Admin
		//	will need to change this to Admin_, changing links for /Admin to /Admin_Main as of 1.7b2
		if( strpos($lower_title,'admin') === 0 ){
			return 'admin';
		}

		if( strpos($lower_title,'special_') === 0 ){
			return 'special';
		}

		if( isset($gp_index[$title]) ){
			$key = $gp_index[$title];
			$info = $gp_titles[$key];
			if( $info['type'] == 'special' ){
				return 'special';
			}
		}

		return false;
	}


	/**
	 * Return the name of the page being requested based on $_SERVER['REQUEST_URI']
	 * May also redirect the request
	 *
	 * @return string The title to display based on the request uri
	 *
	 */
	function WhichPage(){
		global $config,$gp_internal_redir;

		//backwards support, redirect
		if( isset($_GET['r']) ){
			$path = $_GET['r'];
			$path = gpFiles::CleanTitle($path);
			header('Location: '.common::GetUrl($path,false));
			die();
		}

		if( isset($gp_internal_redir) ){
			return $gp_internal_redir;
		}

		$path = common::CleanRequest($_SERVER['REQUEST_URI']);

		$pos = strpos($path,'?');
		if( $pos !== false ){
			$path = substr($path,0,$pos);
		}

		//$path = trim($path,'/');
		$path = gpFiles::CleanTitle($path);
		$path = gpPlugin::Filter('WhichPage',array($path));

		if( empty($path) ){
			return $config['homepath'];
		}

		//
		if( isset($config['homepath']) && $path == $config['homepath'] ){
			header('Location: '.common::GetUrl('',false));
			die();
		}

		return $path;
	}

	/**
	 * Remove $dirPrefix and index.php from a path to get the page title
	 *
	 * @param string $path A full relative url like /install_dir/index.php/request_title
	 * @param string The request_title portion of $path
	 *
	 */
	function CleanRequest($path){
		global $dirPrefix;

		//use dirPrefix to find requested title
		$path = rawurldecode($path); //%20 ...

		if( !empty($dirPrefix) ){
			$pos = strpos($path,$dirPrefix);
			if( $pos !== false ){
				$path = substr($path,$pos+strlen($dirPrefix));
			}
		}


		//remove /index.php/
		$pos = strpos($path,'/index.php');
		if( $pos === 0 ){
			$path = substr($path,11);
		}

		$path = ltrim($path,'/');

		return $path;
	}

	/* deprecated */
	function get_clean(){
		return ob_get_clean();
	}

	/**
	 * Handle admin login/logout/session_start if admin session parameters exist
	 *
	 */
	function sessions(){

		$start = false;
		$update_cookies = false;
		$cmd = common::GetCommand();
		if( $cmd ){
			$start = true;
		}elseif( isset($_COOKIE[gp_session_cookie]) ){
			$start = true;
		}elseif( isset($_COOKIE['gpEasy']) ){
			$_COOKIE[gp_session_cookie] = $_COOKIE['gpEasy'];
			$update_cookies = true;
			$start = true;
		}

		if( $start === false ){
			return;
		}


		includeFile('tool/sessions.php');
		includeFile('admin/admin_tools.php');

		if( $update_cookies ){
			gpsession::cookie(gp_session_cookie,$_COOKIE['gpEasy']);
			gpsession::cookie('gpEasy','',time()-42000);
		}


		switch( $cmd ){
			case 'logout':
				gpsession::LogOut();
			return;
			case 'login':
				gpsession::LogIn();
			return;
		}

		if( isset($_COOKIE[gp_session_cookie]) ){
			gpsession::CheckPosts($_COOKIE[gp_session_cookie]);
			gpsession::start($_COOKIE[gp_session_cookie]);
		}

	}


	/**
	 * Return true if an administrator is logged in
	 * @return bool
	 */
	function LoggedIn(){
		global $gpAdmin;
		static $loggedin;

		if( isset($loggedin) ){
			return $loggedin;
		}

		if( !isset($gpAdmin) ){
			$loggedin = false;
			return false;
		}

		$loggedin = true;
		return true;
	}

	function new_nonce($action = 'none', $anon = false, $factor = 43200 ){
		global $gpAdmin;

		$nonce = $action;
		if( !$anon && !empty($gpAdmin['username']) ){
			$nonce .= $gpAdmin['username'];
		}

		return common::nonce_hash($nonce, 0, $factor );
	}


	/**
	 * Verify a nonce ($check_nonce)
	 *
	 * @param string $action Should be the same $action that is passed to new_nonce()
	 * @param mixed $check_nonce The user submitted nonce or false if $_REQUEST['_gpnonce'] can be used
	 * @param bool $anon True if the nonce is being used for anonymous users
	 * @param int $factor Determines the length of time the generated nonce will be valid. The default 43200 will result in a 24hr period of time.
	 * @return mixed Return false if the $check_nonce did not pass. 1 or 2 if it passes.
	 *
	 */
	function verify_nonce($action = 'none', $check_nonce = false, $anon = false, $factor = 43200 ){
		global $gpAdmin,$config;

		if( $check_nonce === false ){
			$check_nonce =& $_REQUEST['_gpnonce'];
		}

		if( empty($check_nonce) ){
			return false;
		}

		$nonce = $action;
		if( !$anon ){
			if( empty($gpAdmin['username']) ){
				return false;
			}
			$nonce .= $gpAdmin['username'];
		}

		// Nonce generated 0-12 hours ago
		if( common::nonce_hash( $nonce, 0, $factor ) == $check_nonce ){
			return 1;
		}

		// Nonce generated 12-24 hours ago
		if( common::nonce_hash( $nonce, 1, $factor ) == $check_nonce ){
			return 2;
		}

		// Invalid nonce
		return false;
	}

	/**
	 * Generate a nonce hash
	 *
	 * @param string $nonce
	 * @param int $tick_offset
	 * @param int $factor Determines the length of time the generated nonce will be valid. The default 43200 will result in a 24hr period of time.
	 *
	 */
	function nonce_hash( $nonce, $tick_offset=0, $factor = 43200 ){
		global $config;
		$nonce_tick = ceil( time() / $factor ) - $tick_offset;
		return substr( md5($nonce.$config['gpuniq'].$nonce_tick), -12, 10);
	}




	/*deprecated 2.0b1 */
	function IP($ip,$level=2){

		$temp = explode('.',$ip);

		$i = 0;
		while( $level > $i){
			array_pop($temp);
			$i++;
		}

		$checkIP = array_shift($temp); //don't pad with zero's for first part
		foreach($temp as $num){
			$checkIP .= str_pad($num,3,'0',STR_PAD_LEFT);
		}

		return $checkIP;
	}


	/**
	 * Return the command sent by the user
	 * Don't use $_REQUEST here because SetCookieArgs() uses $_GET
	 */
	function GetCommand($type='cmd'){
		common::SetCookieArgs();

		if( isset($_POST[$type]) ){
			return $_POST[$type];
		}

		if( isset($_GET[$type]) ){
			return $_GET[$type];
		}
		return false;
	}


	/**
	 * Used for receiving arguments from javascript without having to put variables in the $_GET request
	 * nice for things that shouldn't be repeated!
	 */
	function SetCookieArgs(){
		static $done = false;

		if( $done || !gp_cookie_cmd ){
			return;
		}

		//get cookie arguments
		if( empty($_COOKIE['cookie_cmd']) ){
			return;
		}
		$test = $_COOKIE['cookie_cmd'];
		if( $test{0} === '?' ){
			$test = substr($test,1);
		}

		//parse_str will overwrite values in $_GET/$_REQUEST
		parse_str($test,$_GET);
		parse_str($test,$_REQUEST);

		//for requests with verification, we'll set $_POST
		if( !empty($_GET['verified']) ){
			parse_str($test,$_POST);
		}

		$done = true;
	}

	/**
	 * Output Javascript code to set variable defaults
	 *
	 */
	function JsStart(){
		//default gpEasy Variables
		echo 'var gplinks={},gpinputs={},gpresponse={}';
		echo ',gpRem=true';
		echo ',isadmin=false';
		echo ',gpBase="'.common::GetDir_Prefixed('').'"';
		echo ',IE7=false,post_nonce="";';
	}
	/**
	 * Output low priority javascript
	 */
	function JsEnd(){
		echo ';$(function(){$("#powered_by_link.hide").hide();});';
	}

	/**
	 * Return the hash of $arg using the appropriate hashing function for the installation
	 * Note: $config['shahash'] won't be set for install!
	 *
	 */
	function hash($arg){
		global $config;

		if( isset($config['shahash']) && !$config['shahash'] ){
			return md5($arg);
		}
		return sha1($arg);
	}

	function AjaxWarning(){
		global $page,$langmessage;
		$page->ajaxReplace[] = array(0=>'admin_box_data',1=>'',2=>$langmessage['OOPS_Start_over']);
	}


	function IdUrl($request_cmd='cv'){
		global $config, $gpversion, $addonBrowsePath;

		$path = $addonBrowsePath.'/Special_Resources?';

		//command
		$args['cmd'] = $request_cmd;

		$_SERVER += array('SERVER_SOFTWARE'=>'');


		//checkin
		//$args['uniq'] = $config['gpuniq'];
		$args['mdu'] = substr(md5($config['gpuniq']),0,20);
		$args['site'] = common::AbsoluteUrl(''); //keep full path for backwards compat
		$args['gpv'] = $gpversion;
		$args['php'] = phpversion();
		$args['se'] =& $_SERVER['SERVER_SOFTWARE'];
		if( defined('service_provider_id') && is_numeric(service_provider_id) ){
			$args['provider'] = service_provider_id;
		}

		//plugins
		$addon_ids = array();
		if( isset($config['addons']) && is_array($config['addons']) ){
			foreach($config['addons'] as $addon => $addon_info){
				if( isset($addon_info['id']) ){
					$addon_ids[] = $addon_info['id'];
				}
			}
		}

		//themes
		if( isset($config['themes']) && is_array($config['themes']) ){
			foreach($config['themes'] as $addon => $addon_info){
				if( isset($addon_info['id']) ){
					$addon_ids[] = $addon_info['id'];
				}
			}
		}

		$args['as'] = implode('-',$addon_ids);

		return $path . http_build_query($args,'','&');
	}

	function IdReq($img_path){

		//asynchronously fetching doesn't affect page loading
		//error function defined to prevent the default error function in main.js from firing
		echo '<script type="text/javascript" style="display:none !important">';
		//echo '$("body").bind("AdminReady",function(){'; //adminready isn't always on the page where the error exists
		//echo '});';
		echo '$.ajax("'.addslashes($img_path).'",{error:function(){}});';
		echo '</script>';

		//echo '<img src="'.$img_path.'" height="1" width="1" alt="" style="border:0 none !important;height:1px !important;width:1px !important;padding:0 !important;margin:0 !important;"/>';
	}

	//only include error buffer when admin is logged in
	function ErrorBuffer($check_user = true){
		global $wbErrorBuffer, $config, $dataDir, $rootDir;

		if( count($wbErrorBuffer) == 0 ) return;

		if( isset($config['Report_Errors']) && !$config['Report_Errors'] ) return;

		if( $check_user && !common::LoggedIn() ) return;

		$dataDir_len = strlen($dataDir);
		$rootDir_len = strlen($rootDir);
		$img_path = common::IdUrl('er');
		$len = strlen($img_path);
		$i = 0;

		foreach($wbErrorBuffer as $error){

			//remove $dataDir or $rootDir from the filename
			$file_name = str_replace('\\','/',$error['ef'.$i]);
			if( $dataDir_len && strpos($file_name,$dataDir) == 0 ){
				$file_name = substr($file_name,$dataDir_len);
			}elseif( $rootDir_len && strpos($file_name,$rootDir) == 0 ){
				$file_name = substr($file_name,$rootDir_len);
			}
			$error['ef'.$i] = substr($file_name,-100);

			$new_path = $img_path.'&'.http_build_query($error,'','&');

			//maximum length of 2000 characters
			if( strlen($new_path) > 2000 ){
				break;
			}
			$img_path = $new_path;
			$i++;
		}

		common::IdReq($img_path);
		$wbErrorBuffer = array();

	}


	/**
	 * Test if function exists.  Also handles case where function is disabled via Suhosin.
	 * Modified from: http://dev.piwik.org/trac/browser/trunk/plugins/Installation/Controller.php
	 *
	 * @param string $functionName Function name
	 * @return bool True if function exists (not disabled); False otherwise.
	 */
	function function_exists($functionName){
		$functionName = strtolower($functionName);

		// eval() is a language construct
		if($functionName == 'eval'){
			// does not check suhosin.executor.eval.whitelist (or blacklist)
			if(extension_loaded('suhosin')){
				return @ini_get('suhosin.executor.disable_eval') != '1';
			}
			return true;
		}

		if( !function_exists($functionName) ){
			return false;
		}

		if(extension_loaded('suhosin')){
			$blacklist = @ini_get('suhosin.executor.func.blacklist');
			if(!empty($blacklist)){
				$blacklistFunctions = array_map('strtolower', array_map('trim', explode(',', $blacklist)));
				return !in_array($functionName, $blacklistFunctions);
			}

		}
		return true;
	}
}


/**
 * Contains functions for working with data files and directories
 *
 */
class gpFiles{


	/**
	 * Read directory and return an array with files corresponding to $filetype
	 *
	 * @param string $dir The path of the directory to be read
	 * @param mixed $filetype If false, all files in $dir will be included. false=all,1=directories,'php'='.php' files
	 * @return array() List of files in $dir
	 */
	function ReadDir($dir,$filetype='php'){
		$files = array();
		if( !file_exists($dir) ){
			return $files;
		}
		$dh = @opendir($dir);
		if( !$dh ){
			return $files;
		}

		while( ($file = readdir($dh)) !== false){
			if( strpos($file,'.') === 0){
				continue;
			}

			//get all
			if( $filetype === false ){
				$files[$file] = $file;
				continue;
			}

			//get directories
			if( $filetype === 1 ){
				$fullpath = $dir.'/'.$file;
				if( is_dir($fullpath) ){
					$files[$file] = $file;
				}
				continue;
			}


			$dot = strrpos($file,'.');
			if( $dot === false ){
				continue;
			}

			$type = substr($file,$dot+1);

			//if $filetype is an array
			if( is_array($filetype) ){
				if( in_array($type,$filetype) ){
					$files[$file] = $file;
				}
				continue;
			}

			//if $filetype is a string
			if( $type == $filetype ){
				$file = substr($file,0,$dot);
				$files[$file] = $file;
			}

		}
		closedir($dh);

		return $files;

	}


	/**
	 * Read all of the folders and files within $dir and return them in an organized array
	 *
	 * @param string $dir The directory to be read
	 * @return array() The folders and files within $dir
	 *
	 */
	function ReadFolderAndFiles($dir){
		$dh = @opendir($dir);
		if( !$dh ){
			return $files;
		}

		$folders = array();
		$files = array();
		while( ($file = readdir($dh)) !== false){
			if( strpos($file,'.') === 0){
				continue;
			}

			$fullPath = $dir.'/'.$file;
			if( is_dir($fullPath) ){
				$folders[] = $file;
			}else{
				$files[] = $file;
			}
		}
		natcasesort($folders);
		natcasesort($files);
		return array($folders,$files);
	}

	/**
	 * Clean a string for use as a page title (url)
	 * Removes potentially problematic characters
	 * see also CleanTitle() in admin.js
	 *
	 * @param string $title The string to be cleansed
	 * @param string $spaces The string spaces will be replaced with
	 * @return string The cleansed string
	 */
	function CleanTitle($title,$spaces = '_'){

		if( empty($title) ){
			return $title;
		}


		/*
		 * Test subdirectory paths
		 * ! need to update CleanTitle in admin.js
		 */
		if( gptesting ){
			$title = str_replace(array('<','>','|'),array(' '),$title);
			$title = preg_replace('#\.+([\\\\/])#','$1',$title); //remove "./"
		}else{
			$title = str_replace(array('<','>','/','\\','|'),array(' '),$title);
		}

		$title = trim($title);
		if( $spaces ){
			$title = str_replace(' ',$spaces,$title);
		}

		$title = str_replace(array('"',"'",'?','#','*',':'),array(''),$title);


		// Remove control characters
		return preg_replace( '#[[:cntrl:]]#u', '', $title ) ; // 	[\x00-\x1F\x7F]
	}

	/**
	 * Clean a string for use as a page label (displayed title)
	 * Similar to CleanTitle() but less restrictive
	 *
	 * @param string $title The title to be cleansed
	 * @return string The cleansed title
	 */
	function CleanLabel($title){
		$title = str_replace(array('"'),array(''),$title);
		$title = str_replace(array('<','>'),array('_'),$title);
		$title = trim($title);


		// Remove control characters
		return preg_replace( '#[[:cntrl:]]#u', '', $title ) ; // 	[\x00-\x1F\x7F]
	}

	/**
	 * Clean a string that may be used as an internal file path
	 *
	 * @param string $path The string to be cleansed
	 * @return string The cleansed string
	 */
	function CleanArg($path){

		//all forward slashes
		$path = str_replace('\\','/',$path);

		//remove directory style changes
		$path = str_replace(array('../','./','..'),array('','',''),$path);

		//change other characters to underscore
		//$pattern = '#\\.|\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]#';
		$pattern = '#\\||\\:|\\?|\\*|"|<|>|[[:cntrl:]]#u';
		$path = preg_replace( $pattern, '_', $path ) ;

		//reduce multiple slashes to single
		$pattern = '#\/+#';
		$path = preg_replace( $pattern, '/', $path ) ;

		return $path;
	}


	/**
	 * Clean a string of html that may be used as file content
	 *
	 * @param string $text The string to be cleansed. Passed by reference
	 */
	function cleanText(&$text){

		gpFiles::tidyFix($text);
		gpFiles::rmPHP($text);
		gpFiles::FixTags($text);
	}

	/**
	 * Use gpEasy's html parser to check the validity of $text
	 *
	 * @param string $text The html content to be checked. Passed by reference
	 */
	function FixTags(&$text){
		includeFile('tool/HTML_Output.php');
		$gp_html_output = new gp_html_output($text);
		$text = $gp_html_output->result;
	}

	/**
	 * Remove php tags from $text
	 *
	 * @param string $text The html content to be checked. Passed by reference
	 */
	function rmPHP(&$text){
		$search = array('<?','<?php','?>');
		$replace = array('&lt;?','&lt;?php','?&gt;');
		$text = str_replace($search,$replace,$text);
	}

	/**
	 * Use HTML Tidy to validate the $text
	 * Only runs when $config['HTML_Tidy'] is off
	 *
	 * @param string $text The html content to be checked. Passed by reference
	 */
	function tidyFix(&$text){
		global $config;

		if( !function_exists('tidy_parse_string') ){
			return false;
		}
		if( empty($config['HTML_Tidy']) || $config['HTML_Tidy'] == 'off' ){
			return true;
		}


		$options = array();
		$options['wrap'] = 0;						//keeps tidy from wrapping... want the least amount of space changing as possible.. could get rid of spaces between words with the str_replaces below
		$options['doctype'] = 'omit';				//omit, auto, strict, transitional, user
		$options['drop-empty-paras'] = true;		//drop empty paragraphs
		$options['output-xhtml'] = true;			//need this so that <br> will be <br/> .. etc
		$options['show-body-only'] = true;
		$options['hide-comments'] = false;
		//$options['anchor-as-name'] = true;		//default is true, but not alwasy availabel. When true, adds an id attribute to anchor; when false, removes the name attribute... poorly designed, but we need it to be true


		//
		//	php4
		//
		if( function_exists('tidy_setopt') ){
			$options['char-encoding'] = 'utf8';
			gpFiles::tidyOptions($options);
			$tidy = tidy_parse_string($text);
			tidy_clean_repair();

			if( tidy_get_status() === 2){
				// 2 is magic number for fatal error
				// http://www.php.net/manual/en/function.tidy-get-status.php
				$tidyErrors[] = 'Tidy found serious XHTML errors: <br/>'.nl2br(htmlspecialchars( tidy_get_error_buffer($tidy)));
				return false;
			}
			$text = tidy_get_output();

		//
		//	php5
		//
		}else{
			$tidy = tidy_parse_string($text,$options,'utf8');
			tidy_clean_repair($tidy);

			if( tidy_get_status($tidy) === 2){
				// 2 is magic number for fatal error
				// http://www.php.net/manual/en/function.tidy-get-status.php
				$tidyErrors[] = 'Tidy found serious XHTML errors: <br/>'.nl2br(htmlspecialchars( tidy_get_error_buffer($tidy)));
				return false;
			}
			$text = tidy_get_output($tidy);
		}
		return true;
	}

	//for php4
	function tidyOptions($options){
		foreach($options as $key => $value){
			tidy_setopt($key,$value);
		}
	}


	/**
	 * Save the content for a new page in /data/_pages/<title>
	 * @since 1.8a1
	 *
	 */
	function NewTitle($title,$section_content = false,$type='text'){
		global $dataDir;

		if( empty($title) ){
			return false;
		}
		$file = gpFiles::PageFile($title);

		$file_sections = array();
		$file_sections[0]['type'] = $type;
		$file_sections[0]['content'] = $section_content;

		$meta_data = array();
		$meta_data['file_number'] = gpFiles::NewFileNumber();
		$meta_data['file_type'] = $type;

		return gpFiles::SaveArray($file,'meta_data',$meta_data,'file_sections',$file_sections);
	}

	function PageFile($title){
		global $dataDir;
		//global $dataDir, $gp_index, $gp_titles;
		//$index = $gp_index[$title];
		//$info = $gp_titles[$index];
		return $dataDir.'/data/_pages/'.$title.'.php';
	}

	function NewFileNumber(){
		global $config;

		includeFile('admin/admin_tools.php');

		if( !isset($config['file_count']) ){
			$config['file_count'] = 0;
		}
		$config['file_count']++;

		admin_tools::SaveConfig();

		return $config['file_count'];

	}

	/**
	 * Get the meta data for the specified file
	 *
	 * @param string $file
	 * @return array
	 */
	function GetTitleMeta($file){

		$meta_data = array();
		if( file_exists($file) ){
			ob_start();
			include($file);
			ob_end_clean();
		}
		return $meta_data;
	}

	/**
	 * Save a file with content and data to the server
	 * This function will be deprecated in future releases. Using it is not recommended
	 *
	 * @param string $file The path of the file to be saved
	 * @param string $contents The contents of the file to be saved
	 * @param string $code The data to be saved
	 * @param string $time The unix timestamp to be used for the $fileVersion
	 * @return bool True on success
	 */
	function SaveFile($file,$contents,$code=false,$time=false){
		global $gpversion;
		if( $time === false ) $time = time();

		$codeA[] = '<'.'?'.'php';
		$codeA[] = 'defined(\'is_running\') or die(\'Not an entry point...\');';
		$codeA[] = '$fileVersion = \''.$gpversion.'\';';
		$codeA[] = '$fileModTime = \''.$time.'\';';
		if( $code !== false ){
			$codeA[] = $code;
		}
		$codeA[] = '';
		$codeA[] = '?'.'>';


		$contents = implode("\n",$codeA).$contents;
		return gpFiles::Save($file,$contents);
	}

	/**
	 * Save raw content to a file to the server
	 *
	 * @param string $file The path of the file to be saved
	 * @param string $contents The contents of the file to be saved
	 * @param bool $checkDir Whether or not to check to see if the parent directory exists before attempting to save the file
	 * @return bool True on success
	 */
	function Save($file,$contents,$checkDir=true){
		$fp = gpFiles::fopen($file,$checkDir);
		if( !$fp ){
			return false;
		}

		if( fwrite($fp,$contents) === false ){
			fclose($fp);
			return false;
		}

		fclose($fp);
		return true;
	}


	/**
	 * Save array(s) to a $file location
	 * Takes 2n+3 arguments
	 *
	 * @param string $file The location of the file to be saved
	 * @param string $varname The name of the variable being saved
	 * @param array $array The value of $varname to be saved
	 *
	 */
	function SaveArray(){
		global $gpversion;

		$args = func_get_args();
		$count = count($args);
		if( ($count %2 !== 1) || ($count < 3) ){
			trigger_error('Wrong argument count '.$count.' for gpFiles::SaveArray() ');
			return false;
		}
		$file = array_shift($args);

		$start = array();
		$start[] = '<'.'?'.'php';
		$start[] = 'defined(\'is_running\') or die(\'Not an entry point...\');';
		$start[] = '$fileVersion = \''.$gpversion.'\';';
		$start[] = '$fileModTime = \''.time().'\';';
		$start[] = '';
		$start[] = '';
		$data = implode("\n",$start);

		while( count($args) ){
			$varname = array_shift($args);
			$array = array_shift($args);
			$data .= gpFiles::ArrayToPHP($varname,$array);
			$data .= "\n\n";
		}

		return gpFiles::Save($file,$data);
	}

	function ArrayToPHP($varname,&$array){
		return '$'.$varname.' = '.var_export($array,true).';';
	}


	/**
	 * Insert a key-value pair into an associative array
	 *
	 * @param mixed $search_key Value to search for in existing array to insert before
	 * @param mixed $new_key Key portion of key-value pair to insert
	 * @param mixed $new_value Value portion of key-value pair to insert
	 * @param array $array Array key-value pair will be added to
	 * @param int $offset Offset distance from where $search_key was found. A value of 1 would insert after $search_key, a value of 0 would insert before $search_key
	 * @param int $length If length is omitted, nothing is removed from $array. If positive, then that many elements will be removed starting with $search_key + $offset
	 * @return bool True on success
	 */
	function ArrayInsert($search_key,$new_key,$new_value,&$array,$offset=0,$length=0){

		$array_keys = array_keys($array);
		$array_values = array_values($array);

		$insert_key = array_search($search_key,$array_keys);
		if( ($insert_key === null) || ($insert_key === false) ){
			return false;
		}

		array_splice($array_keys,$insert_key+$offset,$length,$new_key);
		array_splice($array_values,$insert_key+$offset,$length,'fill'); //use fill in case $new_value is an array
		$array = array_combine($array_keys, $array_values);
		$array[$new_key] = $new_value;

		return true;
	}


	/**
	 * Replace a key-value pair in an associative array
	 * ArrayReplace() is a shortcut for using gpFiles::ArrayInsert() with $offset = 0 and $length = 1
	 */
	function ArrayReplace($search_key,$new_key,$new_value,&$array){
		return gpFiles::ArrayInsert($search_key,$new_key,$new_value,$array,0,1);
	}


	/**
	 * Open a file for writing
	 * Keep track of files and directories that aren't writable in $gp_not_writable
	 *
	 * @param string $file The path of the file
	 * @param bool $checkDir Whether or not to check the parent directory's existence.
	 * @return bool true on success
	 */
	function fopen($file,$checkDir=true){
		global $gp_not_writable;

		if( file_exists($file) ){
			$fp = fopen($file,'wb');
			if( $fp === false ){
				$gp_not_writable[] = $file;
			}
			return $fp;
		}

		$dir = dirname($file);
		if( $checkDir ){
			if( !file_exists($dir) ){
				gpFiles::CheckDir($dir);
			}
		}

		$fp = fopen($file,'wb');
		if( $fp === false ){
			$gp_not_writable[] = $file;
		}else{
			//chmod($file,0644);
			chmod($file,gp_chmod_file);
		}
		return $fp;
	}

	/**
	 * Check recursively to see if a directory exists, if it doesn't attempt to create it
	 *
	 * @param string $dir The directory path
	 * @param bool $index Whether or not to add an index.hmtl file in the directory
	 * @return bool True on success
	 */
	function CheckDir($dir,$index=true){
		global $config,$checkFileIndex;

		if( !file_exists($dir) ){
			$parent = dirname($dir);
			gpFiles::CheckDir($parent,$index);


			//ftp mkdir
			if( isset($config['useftp']) ){
				if( !gpFiles::FTP_CheckDir($dir) ){
					return false;
				}
			}else{
				if( !mkdir($dir,gp_chmod_dir) ){
					return false;
				}
				chmod($dir,gp_chmod_dir); //some systems need more than just the 0755 in the mkdir() function
			}

		}

		//make sure there's an index.html file
		if( $index && $checkFileIndex ){
			$indexFile = $dir.'/index.html';
			if( !file_exists($indexFile) ){
				gpFiles::Save($indexFile,'<html></html>',false);
			}
		}

		return true;
	}

	function RmDir($dir){
		global $config;

		//ftp
		if( isset($config['useftp']) ){
			return gpFiles::FTP_RmDir($dir);
		}
		return rmdir($dir);
	}



	/* FTP Function */

	function FTP_RmDir($dir){
		$conn_id = gpFiles::FTPConnect();
		$dir = gpFiles::ftpLocation($dir);

		return ftp_rmdir($conn_id,$dir);
	}

	function FTP_CheckDir($dir){
		$conn_id = gpFiles::FTPConnect();
		$dir = gpFiles::ftpLocation($dir);

		if( !ftp_mkdir($conn_id,$dir) ){
			return false;
		}
		return ftp_site($conn_id, 'CHMOD 0777 '. $dir );
	}

	function FTPConnect(){
		global $config;

		static $conn_id = false;

		if( $conn_id ){
			return $conn_id;
		}

		if( empty($config['ftp_server']) ){
			return false;
		}

		$conn_id = @ftp_connect($config['ftp_server'],21,6);
		if( !$conn_id ){
			trigger_error('ftp_connect() failed for server : '.$config['ftp_server']);
			return false;
		}

		$login_result = @ftp_login($conn_id,$config['ftp_user'],$config['ftp_pass'] );
		if( !$login_result ){
			trigger_error('ftp_login() failed for server : '.$config['ftp_server'].' and user: '.$config['ftp_user']);
			return false;
		}
		register_shutdown_function(array('gpFiles','ftpClose'),$conn_id);
		return $conn_id;
	}

	function ftpClose($connection=false){
		if( $connection !== false ){
			@ftp_quit($connection);
		}
	}

	function ftpLocation(&$location){
		global $config,$dataDir;

		$len = strlen($dataDir);
		$temp = substr($location,$len);
		return $config['ftp_root'].$temp;
	}
}

