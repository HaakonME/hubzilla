<?php

/*
	$storage = new OAuth2\Storage\Pdo(\DBA::$dba->db);
	$config = [
		'use_openid_connect' => true,
		'issuer' => \Zotlabs\Lib\System::get_site_name()
	];

	$oauth2_server = new OAuth2\Server($storage,$config);

	$oauth2_server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));
	$oauth2_server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));

	$keyStorage = new OAuth2\Storage\Memory( [ 
		'keys' => [ 
			'public_key' => get_config('system','pubkey'),
			'private_key' => get_config('system','prvkey')
		]
	]);

	$oauth2_server->addStorage($keyStorage,'public_key');
*/