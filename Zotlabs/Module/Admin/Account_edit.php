<?php

namespace Zotlabs\Module\Admin;



class Account_edit {

	function post() {

		$account_id = $_REQUEST['aid'];

		if(! $account_id)
			return;

		$pass1 = trim($_REQUEST['pass1']);
		$pass2 = trim($_REQUEST['pass2']);
		if($pass1 && $pass2 && ($pass1 === $pass2)) {
			$salt = random_string(32);
			$password_encoded = hash('whirlpool', $salt . $pass1);
			$r = q("update account set account_salt = '%s', account_password = '%s', 
				account_password_changed = '%s' where account_id = %d",
				dbesc($salt),
				dbesc($password_encoded),
				dbesc(datetime_convert()),
				intval($account_id)
			);
			if($r)
				info( sprintf( t('Password changed for account %d.'), $account_id). EOL);

		}
		goaway(z_root() . '/admin/accounts');
	}


	function get() {
		if(argc() > 2)
			$account_id = argv(2);

		$x = q("select * from account where account_id = %d limit 1",
			intval($account_id)
		);

		if(! $x) {
			notice ( t('Account not found.') . EOL);
			return '';
		}

		$a = replace_macros(get_markup_template('admin_account_edit.tpl'), [
			'$account' => $x[0],
			'$title' => t('Account Edit'),
			'$pass1' => [ 'pass1', t('New Password'), ' ','' ],
			'$pass2' => [ 'pass2', t('New Password again'), ' ','' ],
			'$submit' => t('Submit'),
			]
		);

		return $a;


	}


}