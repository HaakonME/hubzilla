<?php /** @file */

function profile_activity($changed, $value) {

	if(! local_channel() || ! is_array($changed) || ! count($changed))
		return;

	if(! get_pconfig(local_channel(),'system','post_profilechange'))
		return;

	require_once('include/items.php');

	$self = App::get_channel();

	if(! count($self))
		return;

	$arr = array();
	$arr['mid']         = $arr['parent_mid'] = item_message_id();
	$arr['uid']         = local_channel();
	$arr['aid']         = $self['channel_account_id'];
	$arr['owner_xchan'] = $arr['author_xchan'] = $self['xchan_hash'];

	$arr['item_wall'] = 1;
	$arr['item_origin'] = 1;
	$arr['item_thread_top'] = 1;
	$arr['verb']        = ACTIVITY_UPDATE;
	$arr['obj_type']    = ACTIVITY_OBJ_PROFILE;

	$arr['plink'] = z_root() . '/channel/' . $self['channel_address'] . '/?f=&mid=' . $arr['mid'];
				
	$A = '[url=' . z_root() . '/channel/' . $self['channel_address'] . ']' . $self['channel_name'] . '[/url]';


	$changes = '';
	$t = count($changed);
	$z = 0;
	foreach($changed as $ch) {
		if(strlen($changes)) {
			if ($z == ($t - 1))
				$changes .= t(' and ');
			else
				$changes .= ', ';
		}
		$z ++;
		$changes .= $ch;
	}

	$prof = '[url=' . z_root() . '/profile/' . $self['channel_address'] . ']' . t('public profile') . '[/url]';	

	if($t == 1 && strlen($value)) {
		// if it's a url, the HTML quotes will mess it up, so link it and don't try and zidify it because we don't know what it points to.
 		$value = preg_replace_callback("/([^\]\='".'"'."]|^|\#\^)(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\@\_\~\#\%\$\!\+\,]+)/ism", 'red_zrl_callback', $value);
		// take out the bookmark indicator
		if(substr($value,0,2) === '#^')
			$value = str_replace('#^','',$value);

		$message = sprintf( t('%1$s changed %2$s to &ldquo;%3$s&rdquo;'), $A, $changes, $value);
		$message .= "\n\n" . sprintf( t('Visit %1$s\'s %2$s'), $A, $prof);
	}
	else
		$message = 	sprintf( t('%1$s has an updated %2$s, changing %3$s.'), $A, $prof, $changes);
 

	$arr['body'] = $message;  

	$links   = array();
	$links[] = array('rel' => 'alternate', 'type' => 'text/html', 
		'href' => z_root() . '/profile/' . $self['channel_address']);
	$links[] = array('rel' => 'photo', 'type' => $self['xchan_photo_mimetype'], 
		'href' => $self['xchan_photo_l']); 

	$arr['object'] = json_encode(array(
		'type'  => ACTIVITY_OBJ_PROFILE,
		'title' => $self['channel_name'],
		'id'    => $self['xchan_url'] . '/' . $self['xchan_hash'],
		'link'  => $links
	));

	
	$arr['allow_cid'] = $self['channel_allow_cid'];
	$arr['allow_gid'] = $self['channel_allow_gid'];
	$arr['deny_cid']  = $self['channel_deny_cid'];
	$arr['deny_gid']  = $self['channel_deny_gid'];

	$res = item_store($arr);
	$i = $res['item_id'];

	if($i) {
		// FIXME - limit delivery in notifier.php to those specificed in the perms argument
	   	Zotlabs\Daemon\Master::Summon(array('Notifier','activity', $i, 'PERMS_R_PROFILE'));
	}

}
