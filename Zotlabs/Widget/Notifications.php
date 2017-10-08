<?php

namespace Zotlabs\Widget;

class Notifications {

	function widget($arr) {

 		if(! get_pconfig(local_channel(), 'system', 'experimental_notif'))
			return;

		$channel = \App::get_channel();

		if(local_channel()) {
			$notifications[] = [
				'type' => 'network',
				'icon' => 'th',
				'severity' => 'secondary',
				'label' => t('Activity'),
				'title' => t('Network Activity Notifications'),
				'viewall' => [
					'url' => 'network',
					'label' => t('View your network activity')
				],
				'markall' => [
					'url' => '#',
					'label' => t('Mark all notifications seen')
				]
			];

			$notifications[] = [
				'type' => 'home',
				'icon' => 'home',
				'severity' => 'danger',
				'label' => t('Home'),
				'title' => t('Channel Home Notifications'),
				'viewall' => [
					'url' => 'channel/' . $channel['channel_address'],
					'label' => t('View your home activity')
				],
				'markall' => [
					'url' => '#',
					'label' => t('Mark all notifications seen')
				]
			];

			$notifications[] = [
				'type' => 'messages',
				'icon' => 'envelope',
				'severity' => 'danger',
				'label' => t('Mail'),
				'title' => t('Private mail'),
				'viewall' => [
					'url' => 'mail/combined',
					'label' => t('View your private mails')
				],
				'markall' => [
					'url' => '#',
					'label' => t('Mark all messages seen')
				]
			];

			$notifications[] = [
				'type' => 'all_events',
				'icon' => 'calendar',
				'severity' => 'secondary',
				'label' => t('Events'),
				'title' => t('Event Calendar'),
				'viewall' => [
					'url' => 'mail/combined',
					'label' => t('View events')
				],
				'markall' => [
					'url' => '#',
					'label' => t('Mark all events seen')
				]
			];

			$notifications[] = [
				'type' => 'intros',
				'icon' => 'users',
				'severity' => 'danger',
				'label' => t('New Connections'),
				'title' => t('New Connections'),
				'viewall' => [
					'url' => 'connections',
					'label' => t('View all connections')
				]
			];

			$notifications[] = [
				'type' => 'files',
				'icon' => 'folder',
				'severity' => 'danger',
				'label' => t('New Files'),
				'title' => t('New files shared with me'),
			];

			$notifications[] = [
				'type' => 'notify',
				'icon' => 'exclamation',
				'severity' => 'danger',
				'label' => t('Notices'),
				'title' => t('Notices'),
				'viewall' => [
					'url' => 'notifications/system',
					'label' => t('View all notices')
				],
				'markall' => [
					'url' => '#',
					'label' => t('Mark all notices seen')
				]
			];
		}

		if(local_channel() && is_site_admin()) {
			$notifications[] = [
				'type' => 'register',
				'icon' => 'user-o',
				'severity' => 'danger',
				'label' => t('New Registrations'),
				'title' => t('New Registrations'),
			];
		}

		$notifications[] = [
			'type' => 'pubs',
			'icon' => 'globe',
			'severity' => 'secondary',
			'label' => t('Public Stream'),
			'title' => t('Public Stream Notifications'),
			'viewall' => [
				'url' => 'pubstream',
				'label' => t('View the public stream')
			],
			'markall' => [
				'url' => '#',
				'label' => t('Mark all notifications seen')
			]
		];


		$o = replace_macros(get_markup_template('notifications_widget.tpl'), array(
			'$notifications' => $notifications,
			'$loading' => t('Loading...')
		));

		return $o;

	}
}
 
