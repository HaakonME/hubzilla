<?php


function tasks_post(&$a) {

	if(! local_channel())
		return;

	$channel = $a->get_channel();

	if((argc() > 2) && (argv(1) === 'complete') && intval(argv(2))) {
		$ret = array('success' => false);
		$r = q("select * from event where `type` = 'task' and uid = %d and id = %d limit 1",
			intval(local_channel()),
			intval(argv(2))
		);
		if($r) {
			$event = $r[0];
			if($event['event_status'] === 'COMPLETED') {
				$event['event_status'] = 'IN-PROCESS';
				$event['event_status_date'] = NULL_DATE;
				$event['event_percent'] = 0;
				$event['event_sequence'] = $event['event_sequence'] + 1;
				$event['edited'] = datetime_convert();
			}
			else {
				$event['event_status'] = 'COMPLETED';
				$event['event_status_date'] = datetime_convert();
				$event['event_percent'] = 100;
				$event['event_sequence'] = $event['event_sequence'] + 1;
				$event['edited'] = datetime_convert();
			}
			$x = event_store_event($event);
			if($x)
				$ret['success'] = true;
		}
		json_return_and_die($ret);
	}

	if(argc() == 2 && argv(1) === 'new') {
		$text = escape_tags(trim($_REQUEST['summary']));
		if(! $text)
			return array('success' => false);
		$event = array();
		$event['aid'] = $channel['channel_account_id'];
		$event['uid'] = $channel['channel_id'];
		$event['event_xchan'] = $channel['channel_hash'];
		$event['type'] = 'task';
		$event['nofinish'] = true;
		$event['created'] = $event['edited'] = $event['start'] = datetime_convert();
		$event['adjust'] = 1;
		$event['allow_cid'] = '<' . $channel['channel_hash'] . '>';
		$event['summary'] = escape_tags($_REQUEST['summary']);
		$x = event_store_event($event);
		if($x)
			$x['success'] = true;
		else
			$x = array('success' => false);
		json_return_and_die($x);
	}

	
}





function tasks_content(&$a) {

	if(! local_channel())
		return;

	$ret = array();
	$sql_extra = " and event_status != 'COMPLETED' ";
	if(argc() > 1 && argv(1) === 'all')
		$sql_extra = '';
dbg(1);
	$r = q("select * from event where type = 'task' and uid = %d $sql_extra ",
		intval(local_channel())
	);
dbg(0);

	$ret['success'] = (($r) ? true : false);
	if($r) {
		$ret['tasks'] = $r;
	}

//	return $ret;		

	return json_encode($ret);

//	json_return_and_die($ret);



}