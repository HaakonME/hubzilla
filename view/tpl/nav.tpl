<div class="container-fluid">
	<div class="navbar-header">
		<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-collapse-2">
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
		</button>
		{{if $nav.login && !$userinfo}}
		<button type="button" class="navbar-toggle navbar-toggle-extra-left" title="{{$nav.loginmenu.1.3}}" id="{{$nav.loginmenu.1.4}}_collapse" data-toggle="modal" data-target="#nav-login">
			{{$nav.loginmenu.1.1}}
		</button>
		{{if $nav.register}}
		<a href="{{$nav.register.0}}" title="{{$nav.register.3}}" id="{{$nav.register.4}}" class="navbar-toggle navbar-toggle-extra-left">
			{{$nav.register.1}}
		</a>
		{{/if}}
		{{/if}}
		{{if $localuser}}
		<button id="notifications-btn" type="button" class="navbar-toggle navbar-toggle-extra" data-toggle="collapse" data-target="#navbar-collapse-1" style="color: grey;">
			<i class="fa fa-exclamation-circle"></i>
		</button>
		{{/if}}
		<button id="expand-tabs" type="button" class="navbar-toggle navbar-toggle-extra" data-toggle="collapse" data-target="#tabs-collapse-1">
			<i class="fa fa-arrow-circle-down" id="expand-tabs-icon"></i>
		</button>
		<button id="expand-aside" type="button" class="navbar-toggle navbar-toggle-extra" data-toggle="offcanvas" data-target="#region_1">
			<i class="fa fa-arrow-circle-right" id="expand-aside-icon"></i>
		</button>
		{{if $nav.help.6}}
		<button id="context-help-btn" class="navbar-toggle navbar-toggle-extra" type="button" onclick="contextualHelp(); return false;">
			<i class="fa fa-question-circle"></i>
		</button>
		{{/if}}
		{{if $userinfo}}
		<div class="usermenu-head dropdown-toggle fakelink" data-toggle="dropdown">
			<img id="avatar" src="{{$userinfo.icon}}" alt="{{$userinfo.name}}">
			<span class="caret" id="usermenu-caret"></span>
		</div>
		{{if $localuser}}
		<ul class="dropdown-menu" role="menu" aria-labelledby="avatar">
			{{foreach $nav.usermenu as $usermenu}}
			<li role="presentation"><a href="{{$usermenu.0}}" title="{{$usermenu.3}}" role="menuitem" id="{{$usermenu.4}}">{{$usermenu.1}}</a></li>
			{{/foreach}}
			{{if $nav.manage}}
			<li role="presentation"><a href="{{$nav.manage.0}}" title="{{$nav.manage.3}}" role="menuitem" id="{{$nav.manage.4}}">{{$nav.manage.1}}</a></li>
			{{/if}}	
			{{if $nav.channels}}
			{{foreach $nav.channels as $chan}}
			<li role="presentation" class="nav-channel-select"><a href="manage/{{$chan.channel_id}}" title="{{$chan.channel_name}}" role="menuitem">{{$chan.channel_name}}</a></li>
			{{/foreach}}
			{{/if}}
			<li role="presentation" class="divider"></li>
			{{if $nav.profiles}}
			<li role="presentation"><a href="{{$nav.profiles.0}}" title="{{$nav.profiles.3}}" role="menuitem" id="{{$nav.profiles.4}}">{{$nav.profiles.1}}</a></li>
			{{/if}}
			{{if $nav.settings}}
			<li role="presentation"><a href="{{$nav.settings.0}}" title="{{$nav.settings.3}}" role="menuitem" id="{{$nav.settings.4}}">{{$nav.settings.1}}</a></li>
			{{/if}}
			{{if $nav.admin}}
			<li role="presentation" class="divider"></li>
			<li role="presentation"><a href="{{$nav.admin.0}}" title="{{$nav.admin.3}}" role="menuitem" id="{{$nav.admin.4}}">{{$nav.admin.1}}</a></li>
			{{/if}}
			{{if $nav.logout}}
			<li role="presentation" class="divider"></li>
			<li role="presentation"><a href="{{$nav.logout.0}}" title="{{$nav.logout.3}}" role="menuitem" id="{{$nav.logout.4}}">{{$nav.logout.1}}</a></li>
			{{/if}}
		</ul>
		{{else}}
		{{if $nav.rusermenu}}
		<ul class="dropdown-menu" role="menu" aria-labelledby="avatar">
			<li role="presentation"><a href="{{$nav.rusermenu.0}}" role="menuitem">{{$nav.rusermenu.1}}</a></li>
			<li role="presentation"><a href="{{$nav.rusermenu.2}}" role="menuitem">{{$nav.rusermenu.3}}</a></li>
		</ul>
		{{/if}}
		{{/if}}
		{{/if}}
	</div>
	<div class="collapse navbar-collapse" id="navbar-collapse-1">
		<ul class="nav navbar-nav navbar-left">
			{{if $nav.network}}
			<li class="{{$sel.network}} net-button" style="display: none;">
				<a href="#" title="{{$nav.network.3}}" id="{{$nav.network.4}}" data-toggle="dropdown" rel="#nav-network-menu">
					<i class="fa fa-fw fa-th"></i>
					<span class="net-update badge"></span>
				</a>
				<ul id="nav-network-menu" role="menu" class="dropdown-menu" rel="network">
					<li id="nav-network-see-all"><a href="{{$nav.network.all.0}}">{{$nav.network.all.1}}</a></li>
					<li id="nav-network-mark-all"><a href="#" onclick="markRead('network'); return false;">{{$nav.network.mark.1}}</a></li>
					<li class="empty">{{$emptynotifications}}</li>
				</ul>
			</li>
			{{/if}}
			{{if $nav.home}}
			<li class="{{$sel.home}} home-button" style="display: none;">
				<a class="{{$nav.home.2}}" href="#" title="{{$nav.home.3}}" id="{{$nav.home.4}}" data-toggle="dropdown" rel="#nav-home-menu">
					<i class="fa fa-fw fa-home"></i>
					<span class="home-update badge"></span>
				</a>
				<ul id="nav-home-menu" class="dropdown-menu" rel="home">
					<li id="nav-home-see-all"><a href="{{$nav.home.all.0}}">{{$nav.home.all.1}}</a></li>
					<li id="nav-home-mark-all"><a href="#" onclick="markRead('home'); return false;">{{$nav.home.mark.1}}</a></li>
					<li class="empty">{{$emptynotifications}}</li>
				</ul>
			</li>
			{{/if}}
			{{if $nav.messages}}
			<li class="{{$sel.messages}} mail-button" style="display: none;">
				<a class="{{$nav.messages.2}}" href="#" title="{{$nav.messages.3}}" id="{{$nav.messages.4}}" data-toggle="dropdown" rel="#nav-messages-menu">
					<i class="fa fa-fw fa-envelope"></i>
					<span class="mail-update badge"></span>
				</a>
				<ul id="nav-messages-menu" class="dropdown-menu" rel="messages">
					<li id="nav-messages-see-all"><a href="{{$nav.messages.all.0}}">{{$nav.messages.all.1}}</a></li>
					<li id="nav-messages-mark-all"><a href="#" onclick="markRead('messages'); return false;">{{$nav.messages.mark.1}}</a></li>
					<li class="empty">{{$emptynotifications}}</li>
				</ul>
			</li>
			{{/if}}
			{{if $nav.all_events}}
			<li class="{{$sel.all_events}} all_events-button" style="display: none;">
				<a class="{{$nav.all_events.2}}" href="#" title="{{$nav.all_events.3}}" id="{{$nav.all_events.4}}" data-toggle="dropdown" rel="#nav-all_events-menu">
					<i class="fa fa-fw fa-calendar"></i>
					<span class="all_events-update badge"></span>
				</a>
				<ul id="nav-all_events-menu" class="dropdown-menu" rel="all_events">
					<li id="nav-all_events-see-all"><a href="{{$nav.all_events.all.0}}">{{$nav.all_events.all.1}}</a></li>
					<li id="nav-all_events-mark-all"><a href="#" onclick="markRead('all_events'); return false;">{{$nav.all_events.mark.1}}</a></li>
					<li class="empty">{{$emptynotifications}}</li>
				</ul>
			</li>
			{{/if}}
			{{if $nav.intros}}
			<li class="{{$sel.intros}} intro-button" style="display: none;">
				<a class="{{$nav.intros.2}}" href="{{$nav.intros.0}}" title="{{$nav.intros.3}}" id="{{$nav.intros.4}}" data-toggle="dropdown" rel="#nav-intros-menu">
					<i class="fa fa-fw fa-users"></i>
					<span class="intro-update badge"></span>
				</a>
				<ul id="nav-intros-menu" class="dropdown-menu" rel="intros">
					<li id="nav-intros-see-all"><a href="{{$nav.intros.all.0}}">{{$nav.intros.all.1}}</a></li>
					<li class="empty">{{$emptynotifications}}</li>
				</ul>
			</li>
			{{/if}}
			{{if $nav.notifications}}
			<li class="{{$sel.notifications}} notify-button" style="display: none;">
				<a href="{{$nav.notifications.0}}" title="{{$nav.notifications.1}}" id="{{$nav.notifications.4}}" data-toggle="dropdown" rel="#nav-notify-menu">
					<i class="fa fa-fw fa-exclamation"></i>
					<span class="notify-update badge"></span>
				</a>
				<ul id="nav-notify-menu" class="dropdown-menu" rel="notify">
					<li id="nav-notify-see-all"><a href="{{$nav.notifications.all.0}}">{{$nav.notifications.all.1}}</a></li>
					<li id="nav-notify-mark-all"><a href="#" onclick="markRead('notify'); return false;">{{$nav.notifications.mark.1}}</a></li>
					<li class="empty">{{$emptynotifications}}</li>
				</ul>
			</li>
			{{/if}}
			{{if $nav.login && !$userinfo}}
			<li class="">
				<a href="#" title="{{$nav.loginmenu.1.3}}" id="{{$nav.loginmenu.1.4}}" data-toggle="modal" data-target="#nav-login">{{$nav.loginmenu.1.1}}</a>
			</li>
			{{/if}}
			{{if $nav.register}}
			<li class="{{$nav.register.2}} hidden-xs"><a href="{{$nav.register.0}}" title="{{$nav.register.3}}" id="{{$nav.register.4}}">{{$nav.register.1}}</a></li>
			{{/if}}
			{{if $nav.alogout}}
			<li class="{{$nav}}-alogout.2 hidden-xs"><a href="{{$nav.alogout.0}}" title="{{$nav.alogout.3}}" id="{{$nav.alogout.4}}">{{$nav.alogout.1}}</a></li>
			{{/if}}
		</ul>
		<ul class="nav navbar-nav navbar-right hidden-xs">
			<li class="">
				<form method="get" action="search" role="search">
					<div id="nav-search-spinner"></div><input class="fa-search" id="nav-search-text" type="text" value="" placeholder="&#xf002; {{$help}}" name="search" title="{{$nav.search.3}}" onclick="this.submit();"/>
				</form>
			</li>
			{{if $nav.help.6}}
			<li class="{{$sel.help}}">
				<a class="{{$nav.help.2}}" target="hubzilla-help" href="{{$nav.help.0}}" title="{{$nav.help.3}}" id="{{$nav.help.4}}" onclick="contextualHelp(); return false;"><i class="fa fa-question-circle"></i></a>
			</li>
			{{/if}}
			<li class="">
				<a href="#" data-toggle="dropdown"><i class="fa fa-bars"></i></a>
				<ul class="dropdown-menu">
					{{foreach $navapps as $navapp}}
					{{$navapp}}
					{{/foreach}}
					{{if $localuser}}
					<li class="divider"></li>
					<li><a href="/apps"><i class="generic-icons-nav fa fa-fw fa-plus-circle"></i>{{$addapps}}</a></li>
					{{/if}}
				</ul>
			</li>
		</ul>
	</div>
	<div class="collapse navbar-collapse" id="navbar-collapse-2">
		<ul class="nav navbar-nav navbar-left hidden-sm hidden-md hidden-lg">
			{{foreach $navapps as $navapp}}
			{{$navapp}}
			{{/foreach}}
			{{if $localuser}}
			<li class="divider"></li>
			<li><a href="/apps"><i class="generic-icons-nav fa fa-fw fa-plus-circle"></i>{{$addapps}}</a></li>
			{{/if}}
		</ul>
	</div>

	{{if $nav.help.6}}
	<div id="contextual-help-content" class="contextual-help-content">
		{{$nav.help.5}}
		<div class="pull-right">
			<a class="btn btn-primary btn-xs" target="hubzilla-help" href="{{$nav.help.0}}" title="{{$nav.help.3}}"><i class="fa fa-fw fa-question"></i>&nbsp;{{$fulldocs}}</a>
			<a class="contextual-help-tool" href="#" onclick="contextualHelp(); return false;"><i class="fa fa-fw fa-times"></i></a>
		</div>
	</div>
	{{/if}}
</div>
