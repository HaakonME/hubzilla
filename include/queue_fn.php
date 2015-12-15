<?php /** @file */

function update_queue_time($id) {
	logger('queue: requeue item ' . $id);
	q("UPDATE outq SET outq_updated = '%s' WHERE outq_hash = '%s'",
		dbesc(datetime_convert()),
		dbesc($id)
	);
}

function remove_queue_item($id) {
	logger('queue: remove queue item ' . $id);
	q("DELETE FROM outq WHERE hash = '%s'",
		dbesc($id)
	);
}


function queue_insert($arr) {

	$x = q("insert into outq ( outq_hash, outq_account, outq_channel, outq_driver, outq_posturl, outq_async, outq_created, 
		outq_updated, outq_notify, outq_msg ) values ( '%s', %d, %d, '%s', '%s', %d, '%s', '%s', '%s', '%s' )",
		dbesc($arr['hash']),
		intval($arr['account_id']),
		intval($arr['channel_id']),
		dbesc(($arr['driver']) ? $arr['driver'] : 'zot'),
		dbesc($arr['posturl']),
		intval(1),
		dbesc(datetime_convert()),
		dbesc(datetime_convert()),
		dbesc($arr['notify']),
		dbesc(($arr['msg']) ? $arr['msg'] : '')
	);
	return $x;

}

