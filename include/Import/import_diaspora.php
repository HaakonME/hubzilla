<?php

require_once('include/bb2diaspora.php');
require_once('include/group.php');

function import_diaspora($data) {
	$a = get_app();

	$account = $a->get_account();
	if(! $account)
		return false;


	$c = create_identity(array(
		'name' => $data['user']['name'],
		'nickname' => $data['user']['username'],
		'account_id' => $account['account_id'],
		'permissions_role' => 'social'
	));

	
	if(! $c['success'])
		return;

	$channel_id = $c['channel']['channel_id'];

	// todo - add nsfw, auto follow, (and strip exif in hubzilla)

	$location = escape_tags($data['user']['profile']['location']);
	if(! $location)
		$location = '';

	q("update channel set channel_location = '%s' where channel_id = %d",
		dbesc($location)
	);


	$photos = import_profile_photo($data['user']['profile']['image_url'],$c['channel']['channel_hash']);
	if($photos[4])
		$photodate = NULL_DATE;
	else
		$photodate = $xchan['xchan_photo_date'];

	$r = q("update xchan set xchan_photo_l = '%s', xchan_photo_m = '%s', xchan_photo_s = '%s', xchan_photo_mimetype = '%s', xchan_photo_date = '%s'
		where xchan_hash = '%s'",
		dbesc($photos[0]),
		dbesc($photos[1]),
		dbesc($photos[2]),
		dbesc($photos[3]),
		dbesc($photodate),
		dbesc($c['channel']['channel_hash'])
	);


	$gender = escape_tags($data['user']['profile']['gender']);
	$about = diaspora2bb($data['user']['profile']['bio']);
	if($data['user']['profile']['birthday'])
		$dob = datetime_convert('UTC','UTC',$data['user']['profile']['birthday'],'Y-m-d');
	else
		$dob = '0000-00-00';

	$r = q("update profile set gender = '%s', about = '%s', dob = '%s' where uid = %d",
		dbesc($gender),
		dbesc($about),
		dbesc($dob),
		intval($channel_id)
	);

	if($data['aspects']) {
		foreach($data['aspects'] as $aspect) {
			group_add($channel_id,escape_tags($aspect['name']),intval($aspect['contacts_visible']));
		}
	} 
	
	// now add connections and send friend requests






	// Then add items - note this can't be done until Diaspora adds guids to exported 
	// items and comments




	proc_run('php','include/notifier.php','location',$channel_id);

	// This will indirectly perform a refresh_all *and* update the directory

	proc_run('php', 'include/directory.php', $channel_id);

	notice( t('Import completed.') . EOL);

	change_channel($channel_id);

	goaway(z_root() . '/network' );

}