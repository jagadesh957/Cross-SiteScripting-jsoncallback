<?php

/*

Custom SESSIONS

*/

defined('gp_lock_time') or define('gp_lock_time',900); // = 15 minutes
//defined('gp_lock_time') or define('gp_lock_time',120); // = 2 minutes .. used for testing

class gpsession{


	function LogIn(){
		global $dataDir,$langmessage,$page,$gp_internal_redir, $config;

		// check nonce
		// expire the nonce after 10 minutes
		if( !common::verify_nonce( 'login_nonce', $_POST['login_nonce'], true, 300 ) ){
			message($langmessage['OOPS'].' (Expired Nonce)');
			return;
		}

		if( !isset($_COOKIE['g']) && !isset($_COOKIE[gp_session_cookie]) ){
			message($langmessage['COOKIES_REQUIRED']);
			$gp_internal_redir = 'Admin_Main';
			return false;
		}

		//delete the entry in $sessions if we're going to create another one with login
		if( isset($_COOKIE[gp_session_cookie]) ){
			gpsession::CleanSession($_COOKIE[gp_session_cookie]);
		}


		include($dataDir.'/data/_site/users.php');
		$username = gpsession::GetLoginUser($users);
		if( $username === false ){
			gpsession::IncorrectLogin('1');
			return false;
		}
		$users[$username] += array('attempts'=> 0,'granted'=>'');
		$userinfo =& $users[$username];

		//Check Attempts
		if( $userinfo['attempts'] >= 5 ){
			$timeDiff = (time() - $userinfo['lastattempt'])/60; //minutes
			if( $timeDiff < 10 ){
				message($langmessage['LOGIN_BLOCK'],ceil(10-$timeDiff));
				$gp_internal_redir = 'Admin_Main';
				return false;
			}
		}


		//check against password sent to a user's email address from the forgot_password form
		$passed = false;
		if( !empty($userinfo['newpass']) && gpsession::CheckPassword($userinfo['newpass']) ){
			$userinfo['password'] = $userinfo['newpass'];
			$passed = true;

		//check password
		}elseif( gpsession::CheckPassword($userinfo['password']) ){
			$passed = true;
		}

		//if passwords don't match
		if( $passed !== true ){
			gpsession::IncorrectLogin('2');
			gpsession::UpdateAttempts($users,$username);
			return false;
		}

		//will be saved in UpdateAttempts
		if( isset($userinfo['newpass']) ){
			unset($userinfo['newpass']);
		}

		//update the session files to .php files
		//changes to $userinfo will be saved by UpdateAttempts() below
		$userinfo = gpsession::SetSessionFileName($userinfo,$username);


		//logged in!
		$logged_in = gpsession::create($userinfo['file_name']);
		if( $logged_in === true ){
			global $gpAdmin;
			$gpAdmin['username'] = $username;
			$gpAdmin['granted'] = $userinfo['granted'];
			message($langmessage['logged_in']);
		}elseif( $logged_in === 'locked' ){
			//message already sent
			$logged_in = false;
		}else{
			message($langmessage['OOPS']);
		}

		//need to save the user info regardless of success or not
		gpsession::UpdateAttempts($users,$username,true);

		return $logged_in;
	}

	/**
	 * Return the username for the login request
	 *
	 */
	function GetLoginUser($users){

		foreach($users as $username => $info){
			$sha_user = sha1($_POST['login_nonce'].$username);

			if( !gp_require_encrypt
				&& !empty($_POST['username'])
				&& $_POST['username'] == $username
				){
					return $username;
			}

			if( $sha_user === $_POST['user_sha'] ){
				return $username;
			}
		}

		return false;
	}

	//check password, choose between plaintext, md5 encrypted or sha-1 encrypted
	function CheckPassword( $user_pass ){

		// $user_pass is the already encrypted password (md5 or sha)
		// the second level hash is always done with sha
		$nonced_pass = sha1($_POST['login_nonce'].$user_pass);

		//without encryption
		if( !gp_require_encrypt && !empty($_POST['password']) ){
			$pass = common::hash(trim($_POST['password']));
			if( $user_pass === $pass ){
				return true;
			}
			return false;
		}

		//with md5 encryption
		if( isset($config['shahash']) && !$config['shahash'] ){
			if( $nonced_pass === $_POST['pass_md5'] ){
				return true;
			}
			return false;
		}

		//with sha encryption
		if( $nonced_pass === $_POST['pass_sha'] ){
			return true;
		}

		return false;
	}


	function IncorrectLogin($i){
		global $langmessage, $gp_internal_redir;
		message($langmessage['incorrect_login'].' ('.$i.')');
		$url = common::GetUrl('Admin?cmd=forgotten');
		message($langmessage['forgotten_password'],$url);
		$gp_internal_redir = 'Admin_Main';

	}


	//get/set the value of $userinfo['file_name']
	function SetSessionFileName($userinfo,$username){

		if( !isset($userinfo['file_name']) ){

			if( isset($userinfo['cookie_id']) ){
				$old_file_name = 'gpsess_'.$userinfo['cookie_id'];
				unset($userinfo['cookie_id']);
			}else{
				//$old_file_name = 'gpsess_'.md5($username.$pass);
				$old_file_name = 'gpsess_'.md5($username.$userinfo['password']);

			}
			$userinfo['file_name'] = gpsession::UpdateFileName($old_file_name);
		}
		return $userinfo;
	}

	function UpdateFileName($old_file_name){
		global $dataDir;

		//get a new unique name
		do{
			$new_file_name = 'gpsess_'.common::RandomString(40).'.php';
			$new_file = $dataDir.'/data/_sessions/'.$new_file_name;
		}while( file_exists($new_file) );


		$old_file = $dataDir.'/data/_sessions/'.$old_file_name;
		if( !file_exists($old_file) ){
			return $new_file_name;
		}

		if( rename($old_file,$new_file) ){
			return $new_file_name;
		}
		return $old_file_name;
	}

	function LogOut(){
		global $langmessage;

		if( !isset($_COOKIE[gp_session_cookie]) ){
			return false;
		}

		gpsession::cookie(gp_session_cookie,'',time()-42000);
		gpsession::CleanSession($_COOKIE[gp_session_cookie]);
		message($langmessage['LOGGED_OUT']);
	}

	function CleanSession($session_id){
		//remove the session_id from session_ids.php
		$sessions = gpsession::GetSessionIds();
		unset($sessions[$_COOKIE[gp_session_cookie]]);
		gpsession::SaveSessionIds($sessions);
	}


	function cookie($name,$value,$expires = false){
		global $config;

		$cookiePath = '/';
		if( !empty($config['dirPrefix']) ){
			$cookiePath = $config['dirPrefix'];
		}
		$cookiePath = str_replace(' ','%20',$cookiePath);


		if( $expires === false ){
			$expires = time()+2592000;
		}elseif( $expires === true ){
			$expires = 0; //expire at end of session
		}

		setcookie($name, $value, $expires, $cookiePath); //need to take care of spaces!
	}




	function UpdateAttempts($users,$username,$reset = false){
		global $dataDir;

		if( $reset ){
			$users[$username]['attempts'] = 0;
		}else{
			$users[$username]['attempts']++;
		}
		$users[$username]['lastattempt'] = time();
		gpFiles::SaveArray($dataDir.'/data/_site/users.php','users',$users);
	}


	/* read/write handler functions */


	//called when a user logs in
	function create($user_file_name){
		global $dataDir;
		$user_file = $dataDir.'/data/_sessions/'.$user_file_name;


		//get existing session_id
		$sessions = gpsession::GetSessionIds();
		$uid = gpsession::auth_browseruid();
		$session_id = false;
		foreach($sessions as $sess_temp_id => $sess_temp_info){
			if( isset($sess_temp_info['uid']) && $sess_temp_info['uid'] == $uid && $sess_temp_info['file_name'] == $user_file_name ){
				$session_id = $sess_temp_id;
			}
		}

		//create a unique session id if needed
		if( $session_id === false ){
			do{
				$session_id = common::RandomString(40);
			}while( isset($sessions[$session_id]) );
		}

		$expires = !isset($_POST['remember']);
		gpsession::cookie(gp_session_cookie,$session_id,$expires);

		//save session id
		$sessions[$session_id] = array();
		$sessions[$session_id]['file_name'] = $user_file_name;
		$sessions[$session_id]['uid'] = $uid;
		$sessions[$session_id]['time'] = false;
		gpsession::SaveSessionIds($sessions);

		//make sure the user's file exists
		if( $user_file && !file_exists($user_file) ){
			$fp = gpFiles::fopen($user_file);
			fclose($fp);
		}
		return gpsession::start($session_id);
	}

	//get a unique id
	/* deprecated */
	function GenerateId(){
		return common::RandomString(40);
	}

	function GetSessionIds(){
		global $dataDir;
		$sessions = array();
		$sessions_file = $dataDir.'/data/_site/session_ids.php';
		if( file_exists($sessions_file) ){
			require($sessions_file);
		}

		return $sessions;
	}

	function SaveSessionIds($sessions){
		global $dataDir;

		while( $current = current($sessions) ){
			$key = key($sessions);

			//delete if older than
			if( isset($current['time']) && $current['time'] > 0 && ($current['time'] < (time() - 1209600)) ){
			//if( $current['time'] < time() - 2592000 ){ //one month
				unset($sessions[$key]);
				$continue = true;
			}else{
				next($sessions);
			}
		}

		//clean
		$sessions_file = $dataDir.'/data/_site/session_ids.php';
		gpFiles::SaveArray($sessions_file,'sessions',$sessions);
	}


	//start a session
	function start($session_id){
		global $langmessage, $dataDir;

		//get the session file
		$sessions = gpsession::GetSessionIds();
		if( !isset($sessions[$session_id]) ){
			gpsession::cookie(gp_session_cookie,'',time()-42000); //make sure the cookie is deleted
			message($langmessage['Session Expired'].' (timeout)');
			return false;
		}
		$sess_info = $sessions[$session_id];

		//check ~ip, ~user agent ...
		if( gp_browser_auth && isset($sess_info['uid']) ){
			$auth_uid = gpsession::auth_browseruid();
			$auth_uid_legacy = gpsession::auth_browseruid(true);//legacy option added to prevent logging users out, added 2.0b2
			if( ($sess_info['uid'] != $auth_uid) && ($sess_info['uid'] != $auth_uid_legacy) ){
				gpsession::cookie(gp_session_cookie,'',time()-42000); //make sure the cookie is deleted
				message($langmessage['Session Expired'].' (browser auth)');
				return false;
			}
		}


		$session_file = $dataDir.'/data/_sessions/'.$sess_info['file_name'];
		if( ($session_file === false) || !file_exists($session_file) ){
			gpsession::cookie(gp_session_cookie,'',time()-42000); //make sure the cookie is deleted
			message($langmessage['Session Expired'].' (invalid)');
			return false;
		}


		//lock to prevent conflicting edits
		$locked = false;
		$is_last_entry = true;
		$last_sess_id = false;
		$last_sess_time = 0;
		$since_last_session = 0;
		foreach($sessions as $sess_temp_id => $sess_temp_info){
			if( !isset($sess_temp_info['time']) || !$sess_temp_info['time'] ){
				continue;
			}
			if( $last_sess_time < $sess_temp_info['time'] ){
				$last_sess_id = $sess_temp_id;
				$last_sess_time = $sess_temp_info['time'];
			}
		}
		if( $last_sess_id ){

			$since_last_session = time() - $last_sess_time;
			$last_sess_info = $sessions[$last_sess_id];

			if( $last_sess_info['file_name'] != $sess_info['file_name'] ){
				$is_last_entry = false;
				if( $since_last_session < gp_lock_time ){
					$locked = true;
					$expires = ceil( (gp_lock_time - $since_last_session)/60 );
					message( $langmessage['site_locked'].' '.sprintf($langmessage['lock_expires_in'],$expires) );
					return 'locked';
				}
			}
		}

		//prevent browser caching when editing
		Header( 'Last-Modified: ' . gmdate( 'D, j M Y H:i:s' ) . ' GMT' );
		Header( 'Expires: ' . gmdate( 'D, j M Y H:i:s', time() ) . ' GMT' );
		Header( 'Cache-Control: no-store, no-cache, must-revalidate'); // HTTP/1.1
		Header( 'Cache-Control: post-check=0, pre-check=0', false );
		Header( 'Pragma: no-cache' ); // HTTP/1.0



		$gpAdmin = array();
		require($session_file);

		$GLOBALS['gpAdmin'] = $gpAdmin;
		$checksum =& $GLOBALS['gpAdmin']['checksum'];
		$gpAdmin['temp'] = rand(0,100);

		//update to version 1.7a3, add file_editing permission
		if( !isset($fileVersion) && !empty($gpAdmin['granted']) ){
			if( $GLOBALS['gpAdmin']['granted'] != 'all' ){
				$GLOBALS['gpAdmin']['granted'] .= ',file_editing';
				gpsession::AddFileEditing($gpAdmin['username']);
			}
		}

		//fix gpui variabgles
		if( isset($GLOBALS['gpAdmin']['browser_display']) ){
			$GLOBALS['gpAdmin']['gpui_brdis'] = $GLOBALS['gpAdmin']['browser_display'];
			unset($GLOBALS['gpAdmin']['browser_display']);
		}

		if( isset($GLOBALS['gpAdmin']['panelposx']) ){
			$GLOBALS['gpAdmin']['gpui_pposx'] = $GLOBALS['gpAdmin']['panelposx'];
			unset($GLOBALS['gpAdmin']['panelposx']);
		}
		if( isset($GLOBALS['gpAdmin']['panelposy']) ){
			$GLOBALS['gpAdmin']['gpui_pposy'] = $GLOBALS['gpAdmin']['panelposy'];
			unset($GLOBALS['gpAdmin']['panelposy']);
		}

		//$GLOBALS['gpAdmin'] = gpsession::gpui_defaults() + $GLOBALS['gpAdmin']; //reset the defaults
		$GLOBALS['gpAdmin'] += gpsession::gpui_defaults();

		register_shutdown_function(array('gpsession','close'),$session_file,$checksum);

		gpsession::SaveSetting();


		//update time and move to end of $sessions array
		if( !$is_last_entry || ($since_last_session > (gp_lock_time / 2) ) ){
			$sessions[$session_id]['time'] = time();
			gpsession::SaveSessionIds($sessions);
		}

		return true;
	}

	function gpui_defaults(){

		return array(	'gpui_cmpct'=>1,
						'gpui_tx'=>6,
						'gpui_ty'=>130,
						'gpui_ckx'=>20,
						'gpui_cky'=>240,
						'gpui_ckd'=>false,
						'gpui_pposx'=>0,
						'gpui_pposy'=>0,
						'gpui_brdis'=>'browser_icons_small',
						'gpui_pw'=>0,
						'gpui_pdock'=>true,
						'gpui_edb'=>false,
						);
	}




	//update to version 1.7a3
	function AddFileEditing($username){
		global $dataDir;

		$file = $dataDir.'/data/_site/users.php';
		if( !file_exists($file) ){
			return;
		}

		include($file);

		if( !isset($users[$username]) || !isset($users[$username]['granted']) ){
			return;
		}
		$users[$username]['granted'] .= ',file_editing';
		gpFiles::SaveArray($file,'users',$users);

	}


	function CheckPosts($session_id){

		if( count($_POST) == 0 ){
			return;
		}

		if( !isset($_POST['verified']) ){
			gpsession::StripPost();
			return;
		}

		if( !common::verify_nonce('post',$_POST['verified'],true) && ($_POST['verified'] !== $session_id) ){
			gpsession::StripPost();
			return;
		}
	}

	function StripPost(){
		global $langmessage;
		message($langmessage['OOPS'].' (XSS)');
		foreach($_POST as $key => $value){
			unset($_POST[$key]);
		}
	}


	function close($file,$checksum_read){
		global $gpAdmin;

		unset($gpAdmin['checksum']);
		$checksum = gpsession::checksum($gpAdmin);

		//nothing changes
		if( $checksum === $checksum_read ){
			return;
		}
		if( !isset($gpAdmin['username']) ){
			trigger_error('username not set');
			die();
		}

		$gpAdmin['checksum'] = $checksum; //store the new checksum
		gpFiles::SaveArray($file,'gpAdmin',$gpAdmin);
	}


	/* Save user settings */
	function SaveSetting(){

		$cmd = common::GetCommand();
		if( empty($cmd) ){
			return;
		}

		switch($cmd){
			case 'savegpui':
				gpsession::SaveGPUI();
			//dies
		}
	}

	function SaveGPUI(){
		global $gpAdmin;

		gpsession::SetGPUI();

		//send response so an error is not thrown
		echo $_REQUEST['jsoncallback'];
		echo '([]);';
		die();

		//for debugging
		die('debug: '.showArray($_POST).'result: '.showArray($gpAdmin));
	}


	function SetGPUI(){
		global $gpAdmin;

		$possible = array();

		//only change the panel position if it's the default layout
		if( isset($_POST['gpui_dlayout']) && $_POST['gpui_dlayout'] == 'true' ){
			$possible['gpui_pposx']	= 'integer';
			$possible['gpui_pposy']	= 'integer';
			$possible['gpui_pw']	= 'integer';
			$possible['gpui_pdock']	= 'boolean';
		}

		$possible['gpui_brdis']	= array('browser_list'=>1,'browser_icons_small'=>1,'browser_icons'=>1);
		//$possible['gpui_cmpct']	= 'boolean';
		$possible['gpui_cmpct']	= 'integer'; // 0 =
		$possible['gpui_con']	= 'boolean';
		$possible['gpui_cur']	= 'boolean';
		$possible['gpui_app']	= 'boolean';
		$possible['gpui_add']	= 'boolean';
		$possible['gpui_set']	= 'boolean';
		$possible['gpui_upd']	= 'boolean';
		$possible['gpui_use']	= 'boolean';

		$possible['gpui_tx']	= 'integer';
		$possible['gpui_ty']	= 'integer';
		$possible['gpui_ckx']	= 'integer';
		$possible['gpui_cky']	= 'integer';
		$possible['gpui_ckd']	= 'boolean';
		$possible['gpui_edb']	= 'boolean'; //editable bar


		foreach($possible as $key => $key_possible){

			if( !isset($_POST[$key]) ){
				continue;
			}
			$value = $_POST[$key];

			if( $key_possible == 'boolean' ){
				if( !$value || $value === 'false' ){
					$value = false;
				}else{
					$value = true;
				}
			}elseif( $key_possible == 'integer' ){
				$value = (int)$value;
			}elseif( is_array($key_possible) ){
				if( !isset($key_possible[$value]) ){
					continue;
				}
			}

			$gpAdmin[$key] = $value;
		}
	}

	//gpui preferences
	function GPUIVars(){
		global $gpAdmin,$page,$config;


		echo ',gpui={';
		echo 'pposx:'.$gpAdmin['gpui_pposx'];
		echo ',pposy:'.$gpAdmin['gpui_pposy'];
		echo ',pw:'.$gpAdmin['gpui_pw'];
		echo ',pdock:'. ($gpAdmin['gpui_pdock'] ? 'true' : 'false' );
		echo ',brdis:"'.$gpAdmin['gpui_brdis'].'"';
		echo ',cmpct:'.(int)$gpAdmin['gpui_cmpct'];

		//the following control which admin toolbar areas are expanded
		echo ',con:'. (( !isset($gpAdmin['gpui_con']) || $gpAdmin['gpui_con']) ? 'true' : 'false'); //defaults to true
		echo ',cur:'. (( !isset($gpAdmin['gpui_cur']) || $gpAdmin['gpui_cur']) ? 'true' : 'false');
		echo ',app:'. (( isset($gpAdmin['gpui_app']) && $gpAdmin['gpui_app']) ? 'true' : 'false'); //defaults to false
		echo ',add:'. (( isset($gpAdmin['gpui_add']) && $gpAdmin['gpui_add']) ? 'true' : 'false');
		echo ',set:'. (( isset($gpAdmin['gpui_set']) && $gpAdmin['gpui_set']) ? 'true' : 'false');
		echo ',upd:'. (( isset($gpAdmin['gpui_upd']) && $gpAdmin['gpui_upd']) ? 'true' : 'false');
		echo ',use:'. (( isset($gpAdmin['gpui_use']) && $gpAdmin['gpui_use']) ? 'true' : 'false');

		//toolbar location
		echo ',tx:'. $gpAdmin['gpui_tx']; //20
		echo ',ty:'. $gpAdmin['gpui_ty']; //10

		//#ckeditor_area
		echo ',ckx:'. max(5,$gpAdmin['gpui_ckx']);
		echo ',cky:'. max(0,$gpAdmin['gpui_cky']);
		echo ',ckd:'.( !isset($gpAdmin['gpui_ckd']) || !$gpAdmin['gpui_ckd'] ? 'false' : 'true' ); //docked

		//editable bar
		echo ',edb:'.( isset($gpAdmin['gpui_edb']) && $gpAdmin['gpui_edb'] ? 'true' : 'false' ); //show or hide the editable_bar on the left



		//default layout (admin layout)
		if( $page->gpLayout && $page->gpLayout == $config['gpLayout'] ){
			echo ',dlayout:true';
		}else{
			echo ',dlayout:false';
		}

		echo '}';

	}


	/* generic functions */

	function checksum($array){
		return md5(serialize($array) );
	}


	/**
	 * Code modified from dokuwiki
	 * /dokuwiki/inc/auth.php
	 *
	 * Builds a pseudo UID from browser and IP data
	 *
	 * This is neither unique nor unfakable - still it adds some
	 * security. Using the first part of the IP makes sure
	 * proxy farms like AOLs are stil okay.
	 *
	 * @author  Andreas Gohr <andi@splitbrain.org>
	 *
	 * @return  string  a MD5 sum of various browser headers
	 */
	function auth_browseruid($legacy = false){

		$uid = '';
		if( isset($_SERVER['HTTP_USER_AGENT']) ){
			$uid .= $_SERVER['HTTP_USER_AGENT'];
		}
		if( isset($_SERVER['HTTP_ACCEPT_ENCODING']) ){
			$uid .= $_SERVER['HTTP_ACCEPT_ENCODING'];
		}

		// IE does not report ACCEPT_LANGUAGE consistently
		//if( $legacy && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ){
		//	$uid .= $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		//}

		if( isset($_SERVER['HTTP_ACCEPT_CHARSET']) ){
			$uid .= $_SERVER['HTTP_ACCEPT_CHARSET'];
		}

		if( $legacy ){
			if( isset($_SERVER['REMOTE_ADDR']) ){
				$ip = $_SERVER['REMOTE_ADDR'];
				if( strpos($ip,'.') !== false ){
					$uid .= substr($ip,0,strpos($ip,'.'));
				}elseif( strpos($ip,':') !== false ){
					$uid .= substr($ip,0,strpos($ip,':'));
				}
			}
		}else{
			$ip = gpsession::clientIP(true);
			$uid .= substr($ip,0,strpos($ip,'.'));
		}

		//ie8 will report ACCEPT_LANGUAGE as en-us and en-US depending on the type of request (normal, ajax)
		$uid = strtolower($uid);

		return md5($uid);
	}

	/**
	 * Via Dokuwiki
	 * Return the IP of the client
	 *
	 * Honours X-Forwarded-For and X-Real-IP Proxy Headers
	 *
	 * It returns a comma separated list of IPs if the above mentioned
	 * headers are set. If the single parameter is set, it tries to return
	 * a routable public address, prefering the ones suplied in the X
	 * headers
	 *
	 * @param  boolean $single If set only a single IP is returned
	 * @author Andreas Gohr <andi@splitbrain.org>
	 *
	 */
	function clientIP($single=false){
	    $ip = array();
	    $ip[] = $_SERVER['REMOTE_ADDR'];
	    if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
	        $ip = array_merge($ip,explode(',',str_replace(' ','',$_SERVER['HTTP_X_FORWARDED_FOR'])));
	    if(!empty($_SERVER['HTTP_X_REAL_IP']))
	        $ip = array_merge($ip,explode(',',str_replace(' ','',$_SERVER['HTTP_X_REAL_IP'])));

	    // some IPv4/v6 regexps borrowed from Feyd
	    // see: http://forums.devnetwork.net/viewtopic.php?f=38&t=53479
	    $dec_octet = '(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|[0-9])';
	    $hex_digit = '[A-Fa-f0-9]';
	    $h16 = "{$hex_digit}{1,4}";
	    $IPv4Address = "$dec_octet\\.$dec_octet\\.$dec_octet\\.$dec_octet";
	    $ls32 = "(?:$h16:$h16|$IPv4Address)";
	    $IPv6Address =
	        "(?:(?:{$IPv4Address})|(?:".
	        "(?:$h16:){6}$ls32" .
	        "|::(?:$h16:){5}$ls32" .
	        "|(?:$h16)?::(?:$h16:){4}$ls32" .
	        "|(?:(?:$h16:){0,1}$h16)?::(?:$h16:){3}$ls32" .
	        "|(?:(?:$h16:){0,2}$h16)?::(?:$h16:){2}$ls32" .
	        "|(?:(?:$h16:){0,3}$h16)?::(?:$h16:){1}$ls32" .
	        "|(?:(?:$h16:){0,4}$h16)?::$ls32" .
	        "|(?:(?:$h16:){0,5}$h16)?::$h16" .
	        "|(?:(?:$h16:){0,6}$h16)?::" .
	        ")(?:\\/(?:12[0-8]|1[0-1][0-9]|[1-9][0-9]|[0-9]))?)";

	    // remove any non-IP stuff
	    $cnt = count($ip);
	    $match = array();
	    for($i=0; $i<$cnt; $i++){
	        if(preg_match("/^$IPv4Address$/",$ip[$i],$match) || preg_match("/^$IPv6Address$/",$ip[$i],$match)) {
	            $ip[$i] = $match[0];
	        } else {
	            $ip[$i] = '';
	        }
	        if(empty($ip[$i])) unset($ip[$i]);
	    }
	    $ip = array_values(array_unique($ip));
	    if(!$ip[0]) $ip[0] = '0.0.0.0'; // for some strange reason we don't have a IP

	    if(!$single) return join(',',$ip);

	    // decide which IP to use, trying to avoid local addresses
	    $ip = array_reverse($ip);
	    foreach($ip as $i){
	        if(preg_match('/^(::1|[fF][eE]80:|127\.|10\.|192\.168\.|172\.((1[6-9])|(2[0-9])|(3[0-1]))\.)/',$i)){
	            continue;
	        }else{
	            return $i;
	        }
	    }
	    // still here? just use the first (last) address
	    return $ip[0];
	}

}


