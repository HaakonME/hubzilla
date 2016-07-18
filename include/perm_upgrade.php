<?php

function perm_limits_upgrade($channel) {
	set_pconfig($channel['channel_id'],'perm_limits','view_stream',$channel['channel_r_stream']);
	set_pconfig($channel['channel_id'],'perm_limits','view_profile',$channel['channel_r_profile']);
	set_pconfig($channel['channel_id'],'perm_limits','view_contacts',$channel['channel_r_abook']);
	set_pconfig($channel['channel_id'],'perm_limits','view_storage',$channel['channel_r_storage']);
	set_pconfig($channel['channel_id'],'perm_limits','view_pages',$channel['channel_r_pages']);
	set_pconfig($channel['channel_id'],'perm_limits','send_stream',$channel['channel_w_stream']);
	set_pconfig($channel['channel_id'],'perm_limits','post_wall',$channel['channel_w_wall']);
	set_pconfig($channel['channel_id'],'perm_limits','post_comments',$channel['channel_w_comment']);
	set_pconfig($channel['channel_id'],'perm_limits','post_mail',$channel['channel_w_mail']);
	set_pconfig($channel['channel_id'],'perm_limits','post_like',$channel['channel_w_like']);
	set_pconfig($channel['channel_id'],'perm_limits','tag_deliver',$channel['channel_w_tagwall']);
	set_pconfig($channel['channel_id'],'perm_limits','chat',$channel['channel_w_chat']);
	set_pconfig($channel['channel_id'],'perm_limits','write_storage',$channel['channel_w_storage']);
	set_pconfig($channel['channel_id'],'perm_limits','write_pages',$channel['channel_w_pages']);
	set_pconfig($channel['channel_id'],'perm_limits','republish',$channel['channel_a_republish']);
	set_pconfig($channel['channel_id'],'perm_limits','delegate',$channel['channel_a_delegate']);
}


function perm_abook_upgrade($abook) {
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','view_stream',intval(($abook['abook_their_perms'] & PERMS_R_STREAM)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','view_profile',intval(($abook['abook_their_perms'] & PERMS_R_PROFILE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','view_contacts',intval(($abook['abook_their_perms'] & PERMS_R_ABOOK)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','view_storage',intval(($abook['abook_their_perms'] & PERMS_R_STORAGE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','view_pages',intval(($abook['abook_their_perms'] & PERMS_R_PAGES)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','send_stream',intval(($abook['abook_their_perms'] & PERMS_W_STREAM)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','post_wall',intval(($abook['abook_their_perms'] & PERMS_W_WALL)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','post_comments',intval(($abook['abook_their_perms'] & PERMS_W_COMMENT)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','post_mail',intval(($abook['abook_their_perms'] & PERMS_W_MAIL)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','post_like',intval(($abook['abook_their_perms'] & PERMS_W_LIKE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','tag_deliver',intval(($abook['abook_their_perms'] & PERMS_W_TAGWALL)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','chat',intval(($abook['abook_their_perms'] & PERMS_W_CHAT)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','write_storage',intval(($abook['abook_their_perms'] & PERMS_W_STORAGE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','write_pages',intval(($abook['abook_their_perms'] & PERMS_W_PAGES)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','republish',intval(($abook['abook_their_perms'] & PERMS_A_REPUBLISH)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'their_perms','delegate',intval(($abook['abook_their_perms'] & PERMS_A_DELEGATE)? 1 : 0));



	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','view_stream',intval(($abook['abook_my_perms'] & PERMS_R_STREAM)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','view_profile',intval(($abook['abook_my_perms'] & PERMS_R_PROFILE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','view_contacts',intval(($abook['abook_my_perms'] & PERMS_R_ABOOK)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','view_storage',intval(($abook['abook_my_perms'] & PERMS_R_STORAGE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','view_pages',intval(($abook['abook_my_perms'] & PERMS_R_PAGES)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','send_stream',intval(($abook['abook_my_perms'] & PERMS_W_STREAM)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','post_wall',intval(($abook['abook_my_perms'] & PERMS_W_WALL)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','post_comments',intval(($abook['abook_my_perms'] & PERMS_W_COMMENT)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','post_mail',intval(($abook['abook_my_perms'] & PERMS_W_MAIL)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','post_like',intval(($abook['abook_my_perms'] & PERMS_W_LIKE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','tag_deliver',intval(($abook['abook_my_perms'] & PERMS_W_TAGWALL)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','chat',intval(($abook['abook_my_perms'] & PERMS_W_CHAT)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','write_storage',intval(($abook['abook_my_perms'] & PERMS_W_STORAGE)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','write_pages',intval(($abook['abook_my_perms'] & PERMS_W_PAGES)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','republish',intval(($abook['abook_my_perms'] & PERMS_A_REPUBLISH)? 1 : 0));
	set_abconfig($abook['abook_channel'],$abook['abook_xchan'],'my_perms','delegate',intval(($abook['abook_my_perms'] & PERMS_A_DELEGATE)? 1 : 0));


}



