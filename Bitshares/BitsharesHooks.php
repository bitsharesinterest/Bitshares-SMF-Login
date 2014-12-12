<?php
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is http://www.sa-mods.info
 *
 * The Initial Developer of the Original Code is
 * wayne Mankertz.
 * Portions created by the Initial Developer are Copyright (C) 2011
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */
if (!defined('SMF'))
	die('Hacking attempt...');

function bitshares_integrate_logout(){

    if(isset($_SESSION['token']))
	    unset($_SESSION['token']);
}

function bitshares_integrate_login($user, $hashPasswd, $cookieTime){

    global $user_settings;

    if(isset($_GET['syncbts'])){
	
        $gdata = $_SESSION['bitsharesdata'];
        $_SESSION['bitshares']['id'] = $gdata['id'];
	$_SESSION['bitshares']['name'] = $gdata['name'];

	updateMemberData($user_settings['id_member'], 
		array(
			'btsid' => $_SESSION['bitshares']['id'],
			'btsname' => $_SESSION['bitshares']['name'],	
		    )
		    );
   
        unset($_SESSION['bitshares']['id']);
        unset($_SESSION['bitshares']['name']);
        unset($_SESSION['bitsharesdata']);
	
	} else {
	    return;
	}
}

function ob_bitshares(&$buffer){
    global $authUrl, $context, $modSettings, $txt;
	
	if(empty($modSettings['bts_app_enabled']) || isset($_REQUEST['xml']))
	   return $buffer;
	
	if (!$context['user']['is_logged']){ // ok if user is not logged in, then lets create the login buttons at top
	    
		bitshares_init_auth_url();
	    if ((empty($authUrl) || (!$authUrl))) { // empty doesnt catch false
            return "";
        }
	    $txt['guestnew'] = sprintf($txt['welcome_guest'], $txt['guest_title']);
	        
	    $buffer = preg_replace('~(' . preg_quote('<div class="info">'. $txt['guestnew']. '</div>') . ')~', '<a href="'.$authUrl.'"><img src="'.$modSettings['bts_app_custon_logimg'].'" alt="" /></a><div class="info">'. $txt['guestnew']. '</div>', $buffer);	
	    $buffer = preg_replace('~(' . preg_quote($txt['forgot_your_password']. '</a></p>') . ')~', $txt['forgot_your_password']. '</a></p><div align="center"><a href="'.$authUrl.'"><img src="'.$modSettings['bts_app_custon_logimg'].'" alt="" /></a></div>', $buffer);
	    $buffer = preg_replace('~(' . preg_quote('<dt><strong><label for="smf_autov_username">'. $txt['username']. ':</label></strong></dt>') . ')~','<dt><strong>'.$txt['bts_app_rwf'].':</strong><div class="smalltext">'.$txt['bts_app_regmay'].'</div></dt><dd><a href="'.$authUrl.'"><img src="'.$modSettings['bts_app_custon_logimg'].'" alt="" /></a></dd><dt><strong><label for="smf_autov_username">'. $txt['username']. ':</label></strong></dt>', $buffer);
	}
	return $buffer;
}

function bitshares_actions(&$actionArray){
$forum_version = 'SMF 2.0.9';

    $actionArray['bitshares'] = array('Bitshares/Bitshares.php', 'Bitshares');
}

function bitshares_admin_areas(&$admin_areas){
	global $scripturl, $txt;
	
	if(allowedTo('admin_forum')){
        bitshares_array_insert($admin_areas, 'layout',
	        array(
	            'sa_bitshares' => array(
		            'title' => $txt['bts_bitshares'],
		            'areas' => array(
			            'bitshares' => array(
				            'label' => $txt['bts_app_config'],
				           'file' => 'Bitshares/BitsharesAdmin.php',
				            'function' => 'bitsharesa',
				            'custom_url' => $scripturl . '?action=admin;area=bitshares',
				            'icon' => 'server.gif',
			                'subsections' => array(
				                'bitshares' => array($txt['bts_app_config']),
				                'bitshares_logs' => array($txt['bts_app_logs']),
			                ),
						),
		            ),
		        ),
	        )
        );
    }
}

function bitshares_profile_areas(&$profile_areas){
	global $user_settings, $txt, $authUrl, $scripturl, $modSettings, $sc;

	if(empty($user_settings['btsid']) && !empty($modSettings['bts_app_enabled'])){
	    
		bitshares_init_auth_url();
	
		bitshares_array_insert($profile_areas, 'profile_action',
		    array(
			    'profile_bts' => array(
			        'title' => $txt['bts_bitshares'],
			        'areas' => array(
				        'gsettings' => array(
					        'label' => $txt['bts_app_aso_account'],
					        'custom_url' => $authUrl.'" onclick="return confirm(\''.$txt['bts_app_aso_account_confirm'].'\');"',
					        'sc' => $sc,
					        'permission' => array(
						        'own' => 'profile_view_own',
						        'any' => '',
				            ),
				        ),		
			        ),
		        ),
		    )
	    );
	}
	if(!empty($user_settings['btsid']) && !empty($modSettings['bts_app_enabled'])){
	    bitshares_array_insert($profile_areas, 'profile_action',
		    array(
			    'profile_bts' => array(
			        'title' => $txt['bts_bitshares'],
			        'areas' => array(
				        'gsettings' => array(
					        'label' => 'Settings',
					        'file' => 'Bitshares/Bitshares.php',
					        'function' => 'bitshares_Profile',
					        'sc' => $sc,
					        'permission' => array(
						       'own' => 'profile_view_own',
						       'any' => '',
				            ),
				        ),		
			        ),
		        ),
		    )
	    );
	}
}

function bitshares_loadTheme(){
    global $modSettings, $user_info, $context;
	
	loadLanguage('Bitshares');
	
	if (empty($modSettings['allow_guestAccess']) && $user_info['is_guest'] && (isset($_REQUEST['action']) || in_array(isset($_REQUEST['action']), array('bitshares')))) {
	    $modSettings['allow_guestAccess'] = 1;
	}
	
	if(isset($_SESSION['bitshares']['idm']) && isset($_REQUEST['action']) && $_REQUEST['action'] == 'login' && !empty($modSettings['bts_app_enabledautolog'])){
			
		$context['bitshares_id'] = twit_USettings($_SESSION['bitshares']['idm'],'id_member','btsid');
		
		if (!empty($context['bitshares_id'])) {
			redirectexit('action=bitshares;area=connectlog');   
		}
    }	
	
	if (!isset($_REQUEST['xml']))
    {
        $layers = $context['template_layers'];
        $context['template_layers'] = array();
        foreach ($layers as $layer)
        {
            $context['template_layers'][] = $layer;
                if ($layer == 'body' || $layer == 'main')
                    $context['template_layers'][] = 'bitshares';
        }
    }
}

function template_bitshares_above(){ // TODO remove this .. This is that +1 shit
    global $context, $board, $modSettings, $scripturl;

	$show_bitshares = explode(',', !empty($modSettings['bts_app_board_showplus1']) ? $modSettings['bts_app_board_showplus1'] : 0);
	if(in_array($board,$show_bitshares) && !empty($modSettings['bts_app_enabled']) && !empty($context['current_topic']) && !empty($_GET['topic']) && !empty($_GET['action']) != 'post'){
	    echo '<g:plusone href="' . $scripturl . '?topic=' . $context['current_topic'] . '" size="medium"></g:plusone>
	    <script type="text/javascript" src="http://apis.google.com/js/plusone.js"></script>'; // TODO remove this pluson stuff
	}
}

function template_bitshares_below(){}

function bitshares_load(){
    global $boarddir;

    require_once('bitsharesApiClient.php'); // ok so now apiClient should be our class
	
//    require_once($boarddir.'/bitsharesauth/apiClient.php');
//	require_once($boarddir.'/bitsharesauth/contrib/apiOauth2Service.php');

}

function bitshares_init_auth_url(){
    global $authUrl;
	
    bitshares_load();
    try { 	
	    $client = new apiClient();
	    //        $plus = new apiOauth2Service($client); // never referenced
        $authUrl = $client->createAuthUrl(); 
	} 
	catch (Exception $e) {
        $authUrl = '';
    }
}


function bitshares_init_auth(){




    bitshares_load(); // just loads apiClient.php and apiOAuth2Servie.php out of bitsharesauth.. not needed?
    //$client = new apiClient(); // this is google code.. not needed
    //$oauth2 = new apiOauth2Service($client); // This is going to be put into one class...

    $client = new apiClient();
    $oauth2 = $client;
	
    //if (isset($_GET['code'])) {  // this is set after login redirect by google, code is the first token
    if (isset($_GET['signed_secret'])) {
       $client->authenticate();
       $_SESSION['token'] = $client->getAccessToken();
    }
    if (isset($_SESSION['token'])) {
        $client->setAccessToken($_SESSION['token']);
    }

    if ($client->getAccessToken()) {
       $user = $client->userinfo_get();//$oauth2->userinfo->get();/* this puts a 10 element array into $user id,email,verifiedemail,name,givenname,faimlyname,link,picture, generic,locale */
       $_SESSION['token'] = $client->getAccessToken();
    }

    //if(isset($user) ) // We also need to figure out when GET 'code' is and put something similar in TODO
        // actually this may be what causes a redirect loop.. LETS inspect this
    if(isset($user) && isset($_GET['client_key']))  // this was 'code' for oauth, it signfies the first step after browser started process
    { // from the docs it seems to be client_key,server_key, and signed_secret .. go with first in list as default TODO
        $_SESSION['bitsharesdata'] = $user;
	$_SESSION['bitshares']['idm'] = $user['id'];
	$_SESSION['bitshares']['pic'] = !empty($user['picture']) ? $user['picture'] : '';
	redirectexit('action=bitshares;auth=done');
    } 
}

function bitshares_show_auth_login(){
    global $authUrl, $modSettings;
    
	bitshares_init_auth_url();
	echo'<a href="'.$authUrl.'"><img src="'.$modSettings['bts_app_custon_logimg'].'" alt="" /></a>';
}

function bitshares_loadUser($member_id,$where_id) {

	global $smcFunc;

	$results = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}members
		WHERE {raw:where_id} = {string:member_id}
		LIMIT 1',
		array(
			'member_id' => $member_id,
			'where_id' => $where_id,
		)
	);
	$temp = $smcFunc['db_fetch_assoc']($results);
	$smcFunc['db_free_result']($results);

	return $temp;
}

function bitshares_array_insert(&$input, $key, $insert, $where = 'before', $strict = false)
{
	$position = array_search($key, array_keys($input), $strict);
	
	// Key not found -> insert as last
	if ($position === false)
	{
		$input = array_merge($input, $insert);
		return;
	}
	
	if ($where === 'after')
		$position += 1;

	// Insert as first
	if ($position === 0)
		$input = array_merge($insert, $input);
	else
		$input = array_merge(
			array_slice($input, 0, $position),
			$insert,
			array_slice($input, $position)
		);
}
?>