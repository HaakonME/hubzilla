[h3]update_unseen[/h3]

Called prior to automatically marking items 'seen'; allowing a plugin the choice to not perform this action.

hook data

[ 'channel_id' => local_channel(), 'update' => 'unset' ];

If 'update' is set to 0 or false on return, the update operation is not performed.  