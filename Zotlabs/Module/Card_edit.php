<?php
namespace Zotlabs\Module;

require_once('include/channel.php');
require_once('include/acl_selectors.php');
require_once('include/conversation.php');

class Card_edit extends \Zotlabs\Web\Controller {


	function get() {

		// Figure out which post we're editing
		$post_id = ((argc() > 1) ? intval(argv(1)) : 0);

		if(! $post_id) {
			notice( t('Item not found') . EOL);
			return;
		}

		$itm = q("SELECT * FROM item WHERE id = %d and item_type = %d LIMIT 1",
			intval($post_id),
			intval(ITEM_TYPE_CARD)
		);
		if($itm) {
			$item_id = q("select * from iconfig where cat = 'system' and k = 'CARD' and iid = %d limit 1",
				intval($itm[0]['id'])
			);
			if($item_id)
				$card_title = $item_id[0]['v'];
		}
		else {
			notice( t('Item not found') . EOL);
			return;
		}

		$owner = $itm[0]['uid'];
		$uid = local_channel();

		$observer = \App::get_observer();

		$channel = channelx_by_n($owner);
		if(! $channel) {
			notice( t('Channel not found.') . EOL);
			return;
		}

		$ob_hash = (($observer) ? $observer['xchan_hash'] : '');

		if(! perm_is_allowed($owner,$ob_hash,'write_pages')) {
			notice( t('Permission denied.') . EOL);
			return;
		}

		$is_owner = (($uid && $uid == $owner) ? true : false);

		$o = '';



		$category = '';
		$catsenabled = ((feature_enabled($owner,'categories')) ? 'categories' : '');

		if ($catsenabled){
		        $itm = fetch_post_tags($itm);
	
	                $cats = get_terms_oftype($itm[0]['term'], TERM_CATEGORY);
	
		        foreach ($cats as $cat) {
		                if (strlen($category))
		                        $category .= ', ';
		                $category .= $cat['term'];
		        }
		}

		if($itm[0]['attach']) {
			$j = json_decode($itm[0]['attach'],true);
			if($j) {
				foreach($j as $jj) {
					$itm[0]['body'] .= "\n" . '[attachment]' . basename($jj['href']) . ',' . $jj['revision'] . '[/attachment]' . "\n";
				}
			}
		}


		$mimetype = $itm[0]['mimetype'];

		$content = $itm[0]['body'];



		$rp = 'cards/' . $channel['channel_address'];

		$x = array(
			'nickname' => $channel['channel_address'],
			'bbco_autocomplete'=> 'bbcode',
			'return_path' => $rp,
			'webpage' => ITEM_TYPE_CARD,
			'button' => t('Edit'),
			'writefiles' => perm_is_allowed($owner, get_observer_hash(), 'write_pages'),
			'weblink' => t('Insert web link'),
			'hide_voting' => false,
			'hide_future' => false,
			'hide_location' => false,
			'hide_expire' => false,
			'showacl' => true,
			'acl' => populate_acl($itm[0],false,\Zotlabs\Lib\PermissionDescription::fromGlobalPermission('view_pages')),
			'permissions' => $itm[0],
			'lockstate' => (($itm[0]['allow_cid'] || $itm[0]['allow_gid'] || $itm[0]['deny_cid'] || $itm[0]['deny_gid']) ? 'lock' : 'unlock'),
			'ptyp' => $itm[0]['type'],
			'mimeselect' => false,
			'mimetype' => $itm[0]['mimetype'],
			'body' => undo_post_tagging($content),
			'post_id' => $post_id,
			'visitor' => true,
			'title' => htmlspecialchars($itm[0]['title'],ENT_COMPAT,'UTF-8'),
			'placeholdertitle' => t('Title (optional)'),
			'pagetitle' => $card_title,
			'profile_uid' => (intval($channel['channel_id'])),
			'catsenabled' => $catsenabled,
			'category' => $category,
			'bbcode' => (($mimetype  == 'text/bbcode') ? true : false)
		);

		$editor = status_editor($a, $x);

		$o .= replace_macros(get_markup_template('edpost_head.tpl'), array(
			'$title' => t('Edit Card'),
			'$delete' => ((($itm[0]['author_xchan'] === $ob_hash) || ($itm[0]['owner_xchan'] === $ob_hash)) ? t('Delete') : false),
			'$id' => $itm[0]['id'],
			'$editor' => $editor
		));

		return $o;

	}

}
