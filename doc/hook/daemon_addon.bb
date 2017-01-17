[h2]daemon_addon[/h2]


A foreground plugin can create a background process by invoking:

[code]
\Zotlabs\Daemon\Master::Summon([ 'Addon', 'myplugin', 'something' ]);
[/code]

This starts up a background process (called 'Addon') specifically for addons to use. 

Then if your plugin is also catching the daemon_addon hook that handler will be called with the 
argv array of the background process. In this case [ 'myplugin', 'something' ];

We recommend using this convention so that plugins can share this hook without causing conflicts; that is check to see if your plugin is the first array argument and if not, return from the hook. Otherwise you can initiate background processing. Something to remember is that during background processes there is no session. You are detached from the web page which created the background process. 