[h2]channel_links[/h2]

Called when generating the Link HTTP header for the channel page. Different protocol stacks can add links to this header.

Hook data = array
	'channel_address' => channel nickname, no checking is done to see if it is valid
	'channel_links' => array of channel links in the format
		'url'  => url of resource
		'rel'  => link relation
		'type' => MIME type

All fields are required