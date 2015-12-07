<?php

namespace Zotlabs\Zot;

class Receiver {

	protected $data;
	protected $encrypted;
	protected $error;
	protected $messagetype;
	protected $sender;
	protected $validated;
	protected $recipients;
	protected $response;

	function __construct($data,$prvkey) {

		$this->error       = false;
		$this->validated   = false;
		$this->messagetype = '';
		$this->response    = array('success' => false);

		if(! is_array($data))
			$data = json_decode($data,true);

		if($data && is_array($data)) {
			$this->encrypted = ((array_key_exists('iv',$data)) ? true : false);

			if($this->encrypted) {
				$this->data = @json_decode(@crypto_unencapsulate($data,$prvkey),true);
			}
			if(! $this->data)
				$this->data = $data;

			if($this->data && is_array($this->data) && array_key_exists('type',$this->data))
				$this->messagetype = $this->data['type'];
		}
		if(! $this->messagetype)
			$this->error = true;

		$this->sender     = ((array_key_exists('sender',$this->data)) ? $this->data['sender'] : null);
		$this->recipients = ((array_key_exists('recipients',$this->data)) ? $this->data['recipients'] : null);


		if($this->sender)
			$this->ValidateSender();

		$this->Dispatch();
	}

	function ValidateSender() {
		$hubs = zot_gethub($this->sender,true);
		if (! $hubs) {

			/* Have never seen this guid or this guid coming from this location. Check it and register it. */
			/* (!!) this will validate the sender. */

        	$result = zot_register_hub($this->sender);

        	if ((! $result['success']) || (! ($hubs = zot_gethub($this->sender,true)))) {
            	$this->response['message'] = 'Hub not available.';
	            json_return_and_die($this->response);
    	    }
		}
		foreach($hubs as $hub) {
			update_hub_connected($hub,((array_key_exists('sitekey',$this->sender)) ? $this->sender['sitekey'] : ''));
		}
		$this->validated = true;
    }

		
	function Dispatch() {

		/* Handle tasks which don't require sender validation */

		switch($this->messagetype) {
			case 'ping':
				/* no validation needed */
				zot_reply_ping();
				break;
			case 'pickup':
				/* perform site validation, as opposed to sender validation */
				zot_reply_pickup($this->data);
				break;

			default:
				if(! $this->validated) {
					$this->response['message'] = 'Sender not valid';
					json_return_and_die($this->response);
				}
				break;
		}

		/* Now handle tasks which require sender validation */

		switch($this->messagetype) {

			case 'auth_check':
				zot_reply_auth_check($this->data,$this->encrypted);
				break;

			case 'request':
				json_return_and_die(zot_process_message_request($this->data));
				break;

			case 'purge':
				zot_reply_purge($this->sender,$this->recipients);
				break;

			case 'refresh':
			case 'force_refresh':
				zot_reply_refresh($this->sender,$this->recipients);
				break;

			case 'notify':
				zot_reply_notify($this->data);
				break;

			default:
				$this->response['message'] = 'Not implemented';
				json_return_and_die($this->response);
				break;
		}

	}
}
