<?php
defined('is_running') or die('Not an entry point...');



class Install_Tools{

	/**
	 * Display the basic configuration options for installation:
	 *  - Website Title
	 *  - Username
	 *  - Email address (for password recovery)
	 *  - Password
	 *
	 */
	function Form_UserDetails(){
		global $langmessage;

		$_POST += array('username'=>'','site_title'=>'My gpEasy CMS','email'=>'');

		echo '<tr><th colspan="2">'.$langmessage['configuration'].'</th></tr>';
		echo '<tr><td>'.$langmessage['Website_Title'].'</td><td><input type="text" class="text" name="site_title" value="'.htmlspecialchars($_POST['site_title']).'" /></td></tr>';
		echo '<tr><td>'.$langmessage['Admin_Username'].'</td><td><input type="text" class="text" name="username" value="'.htmlspecialchars($_POST['username']).'" /></td></tr>';
		echo '<tr><td>'.$langmessage['email_address'].'</td><td><input type="text" class="text" name="email" value="'.htmlspecialchars($_POST['email']).'" /></td></tr>';
		echo '<tr><td>'.$langmessage['Admin_Password'].'</td><td><input type="password" class="text" name="password" value="" /></td></tr>';
		echo '<tr><td>'.$langmessage['repeat_password'].'</td><td><input type="password" class="text" name="password1" value="" /></td></tr>';
	}

	/**
	 * Display optional configuration options for installation
	 *  - jquery source (local or google)
	 *  - hide gplink
	 *
	 */
	function Form_Configuration(){
		global $langmessage;

		echo '<tr><th colspan="2">';
		echo '<a href="javascript:toggleOptions()">'.$langmessage['more_options'].'...</a>';
		echo '</th></tr>';

		echo '<tbody id="config_options" style="display:none">';


		//combinejs
		echo '<tr><td>';
		echo $langmessage['combinejs'];
		echo '</td><td>';
		Install_Tools::BooleanForm('combinejs',true);
		echo '</td></tr>';


		//combinejs
		echo '<tr><td>';
		echo $langmessage['combinecss'];
		echo '</td><td>';
		Install_Tools::BooleanForm('combinecss',true);
		echo '</td></tr>';

		//combinejs
		echo '<tr><td>';
		echo $langmessage['etag_headers'];
		echo '</td><td>';
		Install_Tools::BooleanForm('etag_headers',true);
		echo '</td></tr>';

		//Hide gpEasy Link
		echo '<tr><td>';
		echo $langmessage['hidegplink'];
		echo '</td><td>';
		echo '<select name="hidegplink">';
		echo '<option value="">Show</option>';
		echo '<option value="hide">Hide</option>';
		echo '</select>';
		echo '</td></tr>';

		echo '</tbody>';

	}

	/**
	 * Display a checkbox for a boolean configuration option
	 *
 	 * @param string $key The configuration key being displayed
 	 * @param bool $default The default value if it hasn't already been set by the user
 	 *
	 */
	function BooleanForm($key,$default=true){
		$checked = '';
		if( Install_Tools::BooleanValue($key,$default) ){
			$checked = 'checked="checked"';
		}
		echo '<input type="hidden" name="'.$key.'" value="false" />';
		echo '<input type="checkbox" name="'.$key.'" value="true" '.$checked.'/>';
	}

	/**
	 * Determine if the boolean configuration option is true or false
	 *
 	 * @param string $key The configuration key
 	 * @param bool $default The default value if it hasn't already been set by the user
	 */
	function BooleanValue($key,$default=true){
		if( !isset($_POST[$key]) ){
			return $default;
		}
		if( $_POST[$key] == 'true' ){
			return true;
		}
		return false;
	}


	//based on the user supplied values, make sure we can go forward with the installation

	function gpInstall_Check(){
		global $langmessage;

		$_POST += array('username'=>'','site_title'=>'My gpEasy CMS','email'=>'');

		$passed = array();
		$failed = array();

		//Email Address
			if( !(bool)preg_match('/^[^@]+@[^@]+\.[^@]+$/', $_POST['email']) ){
				$failed[] = $langmessage['invalid_email'];
			}

		//Password
			if( ($_POST['password']=="") || ($_POST['password'] !== $_POST['password1'])  ){
				$failed[] = $langmessage['invalid_password'];
			}else{
				$passed[] = $langmessage['PASSWORDS_MATCHED'];
			}

		//Username
			$test = str_replace(array('.','_'),array(''),$_POST['username'] );
			if( empty($test) || !ctype_alnum($test) ){
				$failed[] = $langmessage['invalid_username'];
			}else{
				$passed[] = $langmessage['Username_ok'];
			}


		if( count($passed) > 0 ){
			foreach($passed as $message){
				echo '<li class="passed">';
				echo $message;
				echo '</li>';
			}
		}

		if( count($failed) > 0 ){
			foreach($failed as $message){
				echo '<li class="failed">';
				echo $message;
				echo '</li>';
			}
			return false;
		}
		return true;
	}

	function Install_Title(){
		$title = $_POST['site_title'];
		$title = htmlspecialchars($title);
		$title = trim($title);
		if( empty($title) ){
			return 'My gpEasy CMS';
		}
		return $title;
	}

	function Install_DataFiles_New($destination = false, $config, $base_install = true ){
		global $langmessage;


		if( $destination === false ){
			$destination = $GLOBALS['dataDir'];
		}


		//set config variables
		//$config = array(); //because of ftp values

		$gpLayouts = array();
		$gpLayouts['default']['theme'] = 'Light_Texture/Blue';
		$gpLayouts['default']['color'] = '#93c47d';
		$gpLayouts['default']['label'] = $langmessage['default'];


		$config['toemail'] = $_POST['email'];
		$config['gpLayout'] = 'default';
		$config['title'] = Install_Tools::Install_Title();
		$config['keywords'] = 'gpEasy CMS, Easy CMS, Content Management, PHP, Free CMS, Website builder, Open Source';
		$config['desc'] = 'A new gpEasy CMS installation. You can change your site\'s description in the configuration.';
		$config['timeoffset'] = '0';
		$config['langeditor'] = 'inherit';
		$config['dateformat'] = '%m/%d/%y - %I:%M %p';
		$config['gpversion'] = $GLOBALS['gpversion'];
		$config['shahash'] = function_exists('sha1');
		$config['gpuniq'] = common::RandomString(20);
		$config['combinecss'] = Install_Tools::BooleanValue('combinecss',true);
		$config['combinejs'] = Install_Tools::BooleanValue('combinejs',true);
		$config['etag_headers'] = Install_Tools::BooleanValue('etag_headers',true);
		if( isset($_POST['hidegplink']) && $_POST['hidegplink'] == 'hide' ){
			$config['hidegplink'] = 'hide';
		}

		//$config['path_info'] = Install_Tools::GetPathInfo();

		//directories
		gpFiles::CheckDir($destination.'/data/_uploaded/image');
		gpFiles::CheckDir($destination.'/data/_uploaded/media');
		gpFiles::CheckDir($destination.'/data/_uploaded/file');
		gpFiles::CheckDir($destination.'/data/_uploaded/flash');
		gpFiles::CheckDir($destination.'/data/_sessions');


		$content = '<h2>Welcome!</h2>
		<p>Welcome to your new gpEasy powered website. Now that gpEasy is installed, you can start editing the content and customizing your site.</p>
		<h3>Getting Started</h3>
		<p>You are currently viewing the default home page of your website. Here\'s a quick description of how to edit this page.</p>
		<ol>
		<li>First you&#39;ll need to '.Install_Tools::Install_Link_Content('Admin_Main','log in','file=Home').'.</li>
		<li>Then, to edit this page, click the &quot;Edit&quot; link that appears when you move your mouse over the content.</li>
		<li>Make your edits, click &quot;Save&quot; and you&#39;re done!</li>
		</ol>
		<h3>More Options</h3>
		<ul>
		<li>Adding, renaming, deleting and organizing your pages can all be done in the '.Install_Tools::Install_Link_Content('Admin_Menu','Page Manager').'.</li>
		<li>Choose from a '.Install_Tools::Install_Link_Content('Admin_Theme_Content','variety of themes').' to give your site a custom look.</li>
		<li>Then, you can '.Install_Tools::Install_Link_Content('Admin_Theme_Content','add, remove and rearrange','cmd=editlayout').' the content of your site without editing the html.</li>
		<li>Take a look at the Administrator Toolbar to access all the features of gpEasy.</li>
		</ul>
		<h3>Online Resources</h3>
		<p>gpEasy.com has a number of resources to help you do even more with gpEasy.</p>
		<ul>
		<li>Find more community developed <a href="http://gpeasy.com/Special_Addon_Themes" title="gpEasy CMS Themes">themes</a> and <a href="http://gpeasy.com/Special_Addon_Plugins" title="gpEasy CMS Plugin">plugins</a> to enhance your site.</li>
		<li>Get help in the <a href="http://gpeasy.com/Special_Forum" title="gpEasy CMS Forum">gpEasy forum</a>.</li>
		<li>Show off your <a href="http://gpeasy.com/Special_Powered_by" title="Sites Using gpEasy CMS">gpEasy powered site</a> or list your <a href="http://gpeasy.com/Special_Service_Provider" title="Businesses Using gpEasy CMS">gpEasy related business</a>.</li>
		</ul>';


		gpFiles::NewTitle('Home',$content);

		gpFiles::NewTitle('Help_Videos',"<h1>Help Videos</h1>
		<p>Video tutorials are often a fast and easy way to learn new things quickly.
		So far, we only have one in Deutsch (German) made by <a href=\"http://gpeasy.com/Special_Service_Provider?id=57\" title=\"IT Ricther on gpEasy.com\">IT Richter</a>.
		If you make a video tutorial for gpEasy, <a href=\"http://gpeasy.com/Contact\">let us know</a>, and we'll make sure it's included in our list.
		</p>
		<p>And as always, to edit this page, just click the \"Edit\" button while logged in.</p>
		<h2>Deutsch</h2>
		<p><iframe width=\"560\" height=\"315\" src=\"http://www.youtube.com/embed/04cNgR1EiFY\" frameborder=\"0\" allowfullscreen></iframe></p>
		");

		gpFiles::NewTitle('Child_Page','<h1>A Child Page</h1><p>This was created as a subpage of your <em>Help Videos</em> . You can easily change the arrangement of all your pages using the '.Install_Tools::Install_Link_Content('Admin_Menu','Page Manager').'.</p>');

		gpFiles::NewTitle('About','<h1>About gpEasy CMS</h1><p><a href="http://gpEasy.com" title="gpEasy.com">gp|Easy</a> is a complete Content Management System (CMS) that can help you create rich and flexible web sites with a simple and easy to use interface.</p>
		<h2>gpEasy CMS How To</h2>
		<p>Learn how to <a href="http://docs.gpeasy.com/Main/Admin" title="gpEasy File Management">manage your files</a>,
		<a href="http://docs.gpeasy.com/Main/Creating%20Galleries" title="Creating Galleries in gpEasy CMS">create galleries</a> and more in the
		<a href="http://docs.gpeasy.org/index.php/" title="gpEasy CMS Documentation">gpEasy Documentation</a>.
		</p>

		<h2>gpEasy CMS Features</h2>
		<ul>
		<li>True WYSIWYG (Using CKEditor)</li>
		<li>Galleries (Using ColorBox)</li>
		<li>SEO Friendly Links</li>
		<li>Free and Open Source (GPL)</li>
		<li>Runs on PHP</li>
		<li>File Upload Manager</li>
		<li>Drag \'n Drop Theme Content</li>
		<li>Deleted File Trash Can</li>
		<li>Multiple User Administration</li>
		<li>Flat File Storage</li>
		<li>Fast Page Loading</li>
		<li>Fast and Easy Installation</li>
		<li>reCaptcha for Contact Form</li>
		<li>HTML Tidy (when available)</li>
		</ul>
		<h2>If You Like gpEasy...</h2>
		<p>If you like gpEasy, then you might also like:</p>
		<ul>
		<li><a href="http://phpeasymin.com" title="Minimize JavaScript and CSS files easily">phpEasyMin.com</a> - Minimize multiple JavaScript and CSS files in one sweep.</li>
		</ul>');

		//Side_Menu
		$file = $destination.'/data/_extra/Side_Menu.php';
		$content = '<h3>Join the gpEasy Community</h3>
		<p>Visit gpEasy.com to access the many <a href="http://gpeasy.com/Special_Resources" title="gpEasy Community Resources">available resources</a> to help you get the most out of our CMS.</p>
		<ul>
		<li><a href="http://gpeasy.com/Special_Addon_Themes" title="gpEasy CMS Themes">Download Themes</a></li>
		<li><a href="http://gpeasy.com/Special_Addon_Plugins" title="gpEasy CMS Plugin">Download Plugins</a></li>
		<li><a href="http://gpeasy.com/Special_Forum" title="gpEasy CMS Forum">Get Help in the Forum</a></li>
		<li><a href="http://gpeasy.com/Special_Powered_by" title="Sites using gpEasy CMS">Show off Your Site</a></li>
		<li><a href="http://gpeasy.com/Special_Resources" title="gpEasy Community Resources">And Much More...</a></li>
		</ul>
		<p class="sm">(Edit this content by clicking &quot;Edit&quot;, it&#39;s that easy!)</p>';
		gpFiles::SaveFile($file,$content);

		//Header
		$file = $destination.'/data/_extra/Header.php';
		$contents = '<h1>'.Install_Tools::Install_Link('',$config['title']).'</h1>';
		$contents .= '<h4>'.'The Fast and Easy CMS'.'</h4>';
		gpFiles::SaveFile($file,$contents);

		//Footer
		$file = $destination.'/data/_extra/Footer.php';
		$content = '<h3><a href="http://gpeasy.com/Our_CMS" title="Features of Our CMS">gpEasy CMS Features</a></h3>
		<p>Easy to use True WYSIWYG Editing.</p>
		<p>Flat-file data storage and advanced resource management for fast websites.</p>
		<p>Community driven development</p>
		<p><a href="http://gpeasy.com/Our_CMS" title="Features of Our CMS">And More...</a></p>
		<p>If you like gpEasy, then you might also like <a href="http://phpeasymin.com" title="Minimize JavaScript and CSS files easily">phpEasyMin.com</a></p>
		';
		gpFiles::SaveFile($file,$content);

		//contact html
		$file = $destination.'/data/_extra/Contact.php';
		gpFiles::SaveFile($file,'<h2>Contact Us</h2><p>Use the form below to contact us, and be sure to enter a valid email address if you want to hear back from us.</p>');


		// gp_index
		$new_index = array();
		$new_index['Home'] = 'a';
		$new_index['Help_Videos'] = 'b';
		$new_index['Child_Page'] = 'c';
		$new_index['About'] = 'd';
		$new_index['Special_Contact'] = 'e';
		$new_index['Special_Site_Map'] = 'f';
		$new_index['Special_Galleries'] = 'g';
		$new_index['Special_Missing'] = 'h';


		//	gpmenu
		$new_menu = array();
		$new_menu['a'] = array('level'=>0);
		$new_menu['b'] = array('level'=>0);
		$new_menu['c'] = array('level'=>1);
		$new_menu['d'] = array('level'=>0);
		$new_menu['e'] = array('level'=>1);

		//	links
		$new_titles = array();
		$new_titles['a']['label'] = 'Home';
		$new_titles['a']['type'] = 'text';

		$new_titles['b']['label'] = 'Help Videos';
		$new_titles['b']['type'] = 'text';

		$new_titles['c']['label'] = 'Child Page';
		$new_titles['c']['type'] = 'text';

		$new_titles['d']['label'] = 'About';
		$new_titles['d']['type'] = 'text';

		$new_titles['e']['lang_index'] = 'contact';
		$new_titles['e']['type'] = 'special';

		$new_titles['f']['lang_index'] = 'site_map';
		$new_titles['f']['type'] = 'special';

		$new_titles['g']['lang_index'] = 'galleries';
		$new_titles['g']['type'] = 'special';


		$new_titles['h']['label'] = 'Missing';
		$new_titles['h']['type'] = 'special';


		$pages = array();
		$pages['gp_index'] = $new_index;
		$pages['gp_menu'] = $new_menu;
		$pages['gp_titles'] = $new_titles;
		$pages['gpLayouts'] = $gpLayouts;

		echo '<li>';
		if( !gpFiles::SaveArray($destination.'/data/_site/pages.php','pages',$pages) ){
			echo '<span class="failed">';
			//echo 'Could not save pages.php';
			echo sprintf($langmessage['COULD_NOT_SAVE'],'pages.php');
			echo '</span>';
			echo '</li>';
			return false;
		}
		echo '<span class="passed">';
		//echo 'Pages.php saved.';
		echo sprintf($langmessage['_SAVED'],'pages.php');
		echo '</span>';
		echo '</li>';


		//users
		echo '<li>';
		$users = array();

		//sha1 is only available as of php 4.3, fixed in 1.6RC3
		$username = $_POST['username'];
		if( function_exists('sha1') ){
			$users[$username]['password'] = sha1(trim($_POST['password']));
		}else{
			$users[$username]['password'] = md5(trim($_POST['password']));
		}

		$users[$username]['granted'] = 'all';
		$users[$username]['email'] = $_POST['email'];
		if( !gpFiles::SaveArray($destination.'/data/_site/users.php','users',$users) ){
			echo '<span class="failed">';
			echo sprintf($langmessage['COULD_NOT_SAVE'],'users.php');
			//echo 'Could not save users.php';
			echo '</span>';
			echo '</li>';
			return false;
		}
		echo '<span class="passed">';
		echo sprintf($langmessage['_SAVED'],'users.php');
		//echo 'Users.php saved.';
		echo '</span>';
		echo '</li>';



		//save config
		//not using SaveConfig() because $config is not global here
		echo '<li>';
		if( !gpFiles::SaveArray($destination.'/data/_site/config.php','config',$config) ){
		//if( !admin_tools::SaveConfig() ){
			echo '<span class="failed">';
			echo sprintf($langmessage['COULD_NOT_SAVE'],'config.php');
			//echo 'Could not save config.php';
			echo '</span>';
			echo '</li>';
			return false;
		}
		echo '<span class="passed">';
		echo sprintf($langmessage['_SAVED'],'config.php');
		//echo 'Config.php saved.';
		echo '</span>';
		echo '</li>';


		if( $base_install ){
			Install_Tools::InstallHtaccess($destination,$config);
		}

		return true;
	}


	/**
	 * attempt to create an htaccess file
	 * .htaccess creation only works for base_installations because of the $dirPrefix variable
	 * 		This is for the rewrite_rule and TestResponse() which uses AbsoluteUrl()
	 *
	 * @access public
	 * @static
	 * @since 1.7
	 *
	 * @param string $destination The root path of the installation
	 * @param array $config Current installation configuration
	 */
	function InstallHtaccess($destination,$config){
		global $install_ftp_connection, $dirPrefix;

		includeFile('admin/admin_permalinks.php');

		//only proceed with save if we can test the results
		if( !gpRemoteGet::Test() ){
			return;
		}

		$GLOBALS['config']['homepath'] = false; //to prevent a warning from absoluteUrl()
		$file = $destination.'/.htaccess';

		$contents = '';
		$original_contents = false;
		if( file_exists($file) ){
			$original_contents = $contents = file_get_contents($file);
		}

		admin_permalinks::StripRules($contents); //the .htaccess file should not contain any rules
		$contents .= admin_permalinks::Rewrite_Rules(true,$dirPrefix);

		if( !isset($config['useftp']) ){
			//echo 'not using ftp';
			$fp = @fopen($file,'wb');
			if( !$fp ){
				return;
			}

			@fwrite($fp,$contents);
			fclose($fp);
			@chmod($file,0666);

			//return .htaccess to original state
			if( !admin_permalinks::TestResponse() ){
				if( $original_contents === false ){
					unlink($file);
				}else{
					$fp = @fopen($file,'wb');
					if( $fp ){
						@fwrite($fp,$original_contents);
						fclose($fp);
					}
				}
			}
			return;
		}


		//using ftp
		$file = $config['ftp_root'].'/.htaccess';

		$temp = tmpfile();
		if( !$temp ){
			return false;
		}

		fwrite($temp, $contents);
		fseek($temp, 0); //Skip back to the start of the file being written to
		@ftp_fput($install_ftp_connection, $file, $temp, FTP_ASCII );
		fclose($temp);


		//return .htaccess to original state
		if( !admin_permalinks::TestResponse() ){
			if( $original_contents === false ){
				@ftp_delete($install_ftp_connection, $file);
			}else{
				$temp = tmpfile();
				fwrite($temp,$original_contents);
				fseek($temp,0);
				@ftp_fput($install_ftp_connection, $file, $temp, FTP_ASCII );
				fclose($temp);
			}
		}
	}


	function GetPathInfo(){
		$UsePathInfo =
			( strpos( php_sapi_name(), 'cgi' ) === false ) &&
			( strpos( php_sapi_name(), 'apache2filter' ) === false ) &&
			( strpos( php_sapi_name(), 'isapi' ) === false );

		return $UsePathInfo;
	}

	function Install_Link($href,$label,$query='',$attr=''){

		$charlist = "\\'";
		$href = addcslashes($href,$charlist);
		$label = addcslashes($label,$charlist);
		$query = addcslashes($query,$charlist);
		$attr = addcslashes($attr,$charlist);

		$text = '<';
		$text .= '?php';
		$text .= ' echo common::Link(\''.$href.'\',\''.$label.'\',\''.$query.'\',\''.$attr.'\'); ';
		$text .= '?';
		$text .= '>';
		return $text;
	}

	function Install_Link_Content($href,$label,$query='',$attr=''){

		$query = str_replace('&','&amp;',$query);
		$href = str_replace('&','&amp;',$href);

		if( !empty($query) ){
			$query = '?'.$query;
		}

		return '<a href="$linkPrefix/'.$href.$query.'">'.$label.'</a>';
	}

}








/*
 * Functions from skybluecanvas
 *
 *
 */

class FileSystem{

	function GetExpectedPerms($file){

		if( !FileSystem::HasFunctions() ){
			return '777';
		}

		//if user id's match
		$puid = posix_geteuid();
		$suid = FileSystem::file_uid($file);
		if( ($suid !== false) && ($puid == $suid) ){
			return '755';
		}

		//if group id's match
		$pgid = posix_getegid();
		$sgid = FileSystem::file_group($file);
		if( ($sgid !== false) && ($pgid == $sgid) ){
			return '775';
		}

		//if user is a member of group
		$snam = FileSystem::file_owner($file);
		$pmem = FileSystem::process_members();
		if (in_array($suid, $pmem) || in_array($snam, $pmem)) {
			return '775';
		}

		return '777';
	}

	function GetExpectedPerms_file($file){

		if( !FileSystem::HasFunctions() ){
			return '666';
		}

		//if user id's match
		$puid = posix_geteuid();
		$suid = FileSystem::file_uid($file);
		if( ($suid !== false) && ($puid == $suid) ){
			return '644';
		}

		//if group id's match
		$pgid = posix_getegid();
		$sgid = FileSystem::file_group($file);
		if( ($sgid !== false) && ($pgid == $sgid) ){
			return '664';
		}

		//if user is a member of group
		$snam = FileSystem::file_owner($file);
		$pmem = FileSystem::process_members();
		if (in_array($suid, $pmem) || in_array($snam, $pmem)) {
			return '664';
		}

		return '666';
	}

	function HasFunctions(){

		return function_exists('posix_getpwuid')
			&& function_exists('posix_geteuid')
			&& function_exists('fileowner')
			&& function_exists('posix_getegid')
			&& function_exists('posix_getgrgid')
			&& function_exists('posix_getgrgid');
	}


	/*
	 * Compare Permissions
	 */
	function perm_compare($perm1, $perm2) {

		if( !FileSystem::ValidPermission($perm1) ){
			return false;
		}
		if( !FileSystem::ValidPermission($perm2) ){
			return false;
		}

/*
		if (strlen($perm1) != 3) return false;
		if (strlen($perm2) != 3) return false;
*/

		if (intval($perm1{0}) > intval($perm2{0})) {
			return false;
		}
		if (intval($perm1{1}) > intval($perm2{1})) {
			return false;
		}
		if (intval($perm1{2}) > intval($perm2{2})) {
			return false;
		}
		return true;
	}

	function ValidPermission(&$permission){
		if( strlen($permission) == 3 ){
			return true;
		}
		if( strlen($permission) == 4 ){
			if( intval($permission{0}) === 0 ){
				$permission = substr($permission,1);
				return true;
			}
		}
		return false;
	}

	/*
	* @description   Gets name of the file owner
	* @return string The name of the file owner
	*/

	function file_owner($file) {
		$info = FileSystem::file_info($file);
		if (is_array($info)) {
			if (isset($info['name'])) {
				return $info['name'];
			}
			else if (isset($info['uid'])) {
				return $info['uid'];
			}
		}
		return false;
	}


	/*
	* @description  Gets Groups members of the PHP Engine
	* @return array The Group members of the PHP Engine
	*/

	function process_members() {
		$info = FileSystem::process_info();
		if (isset($info['members'])) {
			return $info['members'];
		}
		return array();
	}


	/*
	* @description Gets User ID of the file owner
	* @return int  The user ID of the file owner
	*/

	function file_uid($file) {
		$info = FileSystem::file_info($file);
		if (is_array($info)) {
			if (isset($info['uid'])) {
				return $info['uid'];
			}
		}
		return false;
	}

	/*
	* @description Gets Group ID of the file owner
	* @return int  The user Group of the file owner
	*/

	function file_group($file) {
		$info = FileSystem::file_info($file);
		if (is_array($info) && isset($info['gid'])) {
			return $info['gid'];
		}
		return false;
	}

	/*
	* @description  Gets Info array of the file owner
	* @return array The Info array of the file owner
	*/

	function file_info($file) {
		return posix_getpwuid(@fileowner($file));
	}

	/*
	* @description  Gets Group Info of the PHP Engine
	* @return array The Group Info of the PHP Engine
	*/

	function process_info() {
		return posix_getgrgid(posix_getegid());
	}

}
