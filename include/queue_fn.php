<?php /** @file */

function update_queue_time($id, $add_priority = 0) {
	logger('queue: requeue item ' . $id,LOGGER_DEBUG);
	q("UPDATE outq SET outq_updated = '%s', outq_priority = outq_priority + %d WHERE outq_hash = '%s'",
		dbesc(datetime_convert()),
		intval($add_priority),
		dbesc($id)
	);
}

function remove_queue_item($id,$channel_id = 0) {
	logger('queue: remove queue item ' . $id,LOGGER_DEBUG);
	$sql_extra = (($channel_id) ? " and outq_channel = " . intval($channel_id) . " " : '');
		
	q("DELETE FROM outq WHERE outq_hash = '%s' $sql_extra",
		dbesc($id)
	);
}

function queue_set_delivered($id,$channel = 0) {
	logger('queue: set delivered ' . $id,LOGGER_DEBUG);
	$sql_extra = (($channel_id) ? " and outq_channel = " . intval($channel_id) . " " : '');

	q("update outq set outq_delivered = 1, outq_updated = '%s' where outq_hash = '%s' $sql_extra ",
		dbesc(datetime_convert()),
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

