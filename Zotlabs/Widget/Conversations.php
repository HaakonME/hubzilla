<?php

namespace Zotlabs\Widget;

class Conversations {

	function widget($arr) {

		if (! local_channel())
			return;

		if(argc() > 1) {

			switch(argv(1)) {
				case 'inbox':
					$mailbox = 'inbox';
					$header = t('Received Messages');
					break;
				case 'outbox':
					$mailbox = 'outbox';
					$header = t('Sent Messages');
					break;
				default:
					$mailbox = 'combined';
					$header = t('Conversations');
					break;
			}

			require_once('include/message.php');

			// private_messages_list() can do other more complicated stuff, for now keep it simple
			$r = private_messages_list(local_channel(), $mailbox, \App::$pager['start'], \App::$pager['itemspage']);

			if(! $r) {
				info( t('No messages.') . EOL);
				return $o;
			}

			$messages = array();

			foreach($r as $rr) {

				$selected = ((argc() == 3) ? intval(argv(2)) == intval($rr['id']) : $r[0]['id'] == $rr['id']);

				$messages[] = array(
					'mailbox'      => $mailbox,
					'id'           => $rr['id'],
					'from_name'    => $rr['from']['xchan_name'],
					'from_url'     => chanlink_hash($rr['from_xchan']),
					'from_photo'   => $rr['from']['xchan_photo_s'],
					'to_name'      => $rr['to']['xchan_name'],
					'to_url'       => chanlink_hash($rr['to_xchan']),
					'to_photo'     => $rr['to']['xchan_photo_s'],
					'subject'      => (($rr['seen']) ? $rr['title'] : '<strong>' . $rr['title'] . '</strong>'),
					'delete'       => t('Delete conversation'),
					'body'         => $rr['body'],
					'date'         => datetime_convert('UTC',date_default_timezone_get(),$rr['created'], 'c'),
					'seen'         => $rr['seen'],
					'selected'     => ((argv(1) != 'new') ? $selected : '')
				);
			}

			$tpl = get_markup_template('mail_head.tpl');
			$o .= replace_macros($tpl, array(
				'$header' => $header,
				'$messages' => $messages
			));

		}
		return $o;
	}

}

