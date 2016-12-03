API item/store
==============

Usage: POST /api/z/1.0/item/store

Description: item/store posts an item (typically a conversation item or post, but can be any item) using form input.  


Required:

- body

	text/bbcode contents by default.


Optional:

- $_FILES['media']
	uploaded media file to include with post

- title
	title of post/item

- contact_allow
	array of xchan.xchan_hash allowed to view this item

- group_allow
	array of group.hash allowed to view this item

- contact_deny
	array of xchan.xchan_hash not allowed to view this item

- group_deny
	array of group.hash not allowed to view this item

- coord
	geographic coordinates

- location
	freefrom location

- expire
	datetime this post will expire or be removed

- mimetype
	mimetype if not text/bbcode

- parent
	item.id of parent to this post (makes it a comment)

- parent_mid
	alternate form of parent using message_id

- remote_xchan
	xchan.xchan_hash of this message author if not the channel owner

- consensus
	boolean set to true if this is a consensus or voting item (default false)

- nocomment
	boolean set to true if comments are to be disabled (default false)

- origin
	do not use this without reading the code

- namespace
	persistent identity for a remote network or service

- remote_id
	message_id of this resource on a remote network or service

- message_id
	message_id of this item (leave unset to generate one)

- created
	datetime of message creation

- post_id
	existing item.id if this is an edit operation

- app
	application or network name to display with item

- categories
	comma separated categories for this item

- webpage
	item.page_type if not 0

- pagetitle
	for webpage and design elements, the 'page name'

- layout_mid
	item.mid of layout for this design element

- plink
	permalink for this item if different than the default

- verb
	activitystream verb for this item/activity

- obj_type
	activitystream object type for this item/activity

