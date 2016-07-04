<?php

function perm_limits_upgrade($channel) {
	perm_limits_upgrade_create($channel['channel_id'],'view_stream',$channel['channel_r_stream']);
	perm_limits_upgrade_create($channel['channel_id'],'view_profile',$channel['channel_r_profile']);
	perm_limits_upgrade_create($channel['channel_id'],'view_contacts',$channel['channel_r_abook']);
	perm_limits_upgrade_create($channel['channel_id'],'view_storage',$channel['channel_r_storage']);
	perm_limits_upgrade_create($channel['channel_id'],'view_pages',$channel['channel_r_pages']);
	perm_limits_upgrade_create($channel['channel_id'],'send_stream',$channel['channel_w_stream']);
	perm_limits_upgrade_create($channel['channel_id'],'post_wall',$channel['channel_w_wall']);
	perm_limits_upgrade_create($channel['channel_id'],'post_comments',$channel['channel_w_comment']);
	perm_limits_upgrade_create($channel['channel_id'],'post_mail',$channel['channel_w_mail']);
	perm_limits_upgrade_create($channel['channel_id'],'post_like',$channel['channel_w_like']);
	perm_limits_upgrade_create($channel['channel_id'],'tag_deliver',$channel['channel_w_tagwall']);
	perm_limits_upgrade_create($channel['channel_id'],'chat',$channel['channel_w_chat']);
	perm_limits_upgrade_create($channel['channel_id'],'write_storage',$channel['channel_w_storage']);
	perm_limits_upgrade_create($channel['channel_id'],'write_pages',$channel['channel_w_pages']);
	perm_limits_upgrade_create($channel['channel_id'],'republish',$channel['channel_a_republish']);
	perm_limits_upgrade_create($channel['channel_id'],'delegate',$channel['channel_a_delegate']);
}




function perm_abook_upgrade($abook) {
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','view_stream',intval(($abook['abook_their_perms'] & PERMS_R_STREAM)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','view_profile',intval(($abook['abook_their_perms'] & PERMS_R_PROFILE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','view_contacts',intval(($abook['abook_their_perms'] & PERMS_R_ABOOK)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','view_storage',intval(($abook['abook_their_perms'] & PERMS_R_STORAGE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','view_pages',intval(($abook['abook_their_perms'] & PERMS_R_PAGES)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','send_stream',intval(($abook['abook_their_perms'] & PERMS_W_STREAM)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','post_wall',intval(($abook['abook_their_perms'] & PERMS_W_WALL)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','post_comments',intval(($abook['abook_their_perms'] & PERMS_W_COMMENT)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','post_mail',intval(($abook['abook_their_perms'] & PERMS_W_MAIL)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','post_like',intval(($abook['abook_their_perms'] & PERMS_W_LIKE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','tag_deliver',intval(($abook['abook_their_perms'] & PERMS_W_TAGWALL)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','chat',intval(($abook['abook_their_perms'] & PERMS_W_CHAT)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','write_storage',intval(($abook['abook_their_perms'] & PERMS_W_STORAGE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','write_pages',intval(($abook['abook_their_perms'] & PERMS_W_PAGES)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','republish',intval(($abook['abook_their_perms'] & PERMS_A_REPUBLISH)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'their_perms','delegate',intval(($abook['abook_their_perms'] & PERMS_A_DELEGATE)? 1 : 0));



	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','view_stream',intval(($abook['abook_my_perms'] & PERMS_R_STREAM)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','view_profile',intval(($abook['abook_my_perms'] & PERMS_R_PROFILE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','view_contacts',intval(($abook['abook_my_perms'] & PERMS_R_ABOOK)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','view_storage',intval(($abook['abook_my_perms'] & PERMS_R_STORAGE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','view_pages',intval(($abook['abook_my_perms'] & PERMS_R_PAGES)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','send_stream',intval(($abook['abook_my_perms'] & PERMS_W_STREAM)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','post_wall',intval(($abook['abook_my_perms'] & PERMS_W_WALL)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','post_comments',intval(($abook['abook_my_perms'] & PERMS_W_COMMENT)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','post_mail',intval(($abook['abook_my_perms'] & PERMS_W_MAIL)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','post_like',intval(($abook['abook_my_perms'] & PERMS_W_LIKE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','tag_deliver',intval(($abook['abook_my_perms'] & PERMS_W_TAGWALL)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','chat',intval(($abook['abook_my_perms'] & PERMS_W_CHAT)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','write_storage',intval(($abook['abook_my_perms'] & PERMS_W_STORAGE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','write_pages',intval(($abook['abook_my_perms'] & PERMS_W_PAGES)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','republish',intval(($abook['abook_my_perms'] & PERMS_A_REPUBLISH)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_hash'],'my_perms','delegate',intval(($abook['abook_my_perms'] & PERMS_A_DELEGATE)? 1 : 0));


}