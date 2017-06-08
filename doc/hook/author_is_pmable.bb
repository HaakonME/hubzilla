[h2]author_is_pmable[/h2]

Called from thread action menu before returning a 'send mail' link for the post author. Not all authors will be able to receive private mail, for instance those on other networks with incompatible mail systems. 

By default author_is_pmable() returns true for 'zot' xchans, and false for all others.

The plugin is passed an array

  [ 'xchan' => $author_xchan, 'abook' => abook record, 'result' => 'unset' ]

A plugin which sets the 'result' to something besides 'unset' will over-ride the default behaviour. A value of true will enable the 'send mail' link and the private mail recipient will be set to the author's xchan_hash.  A value of false will disable the 'send mail' link. 



