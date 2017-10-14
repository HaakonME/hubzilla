{{if $nav.login && !$userinfo}}
<div class="d-xl-none pt-1 pb-1">
	{{if $nav.loginmenu.1.4}}
	<a class="btn btn-primary btn-sm text-white" href="#" title="{{$nav.loginmenu.1.3}}" id="{{$nav.loginmenu.1.4}}_collapse" data-toggle="modal" data-target="#nav-login">
		{{$nav.loginmenu.1.1}}
	</a>
	{{else}}
	<a class="btn btn-primary btn-sm text-white" href="login" title="{{$nav.loginmenu.1.3}}">
		{{$nav.loginmenu.1.1}}
	</a>
	{{/if}}
	{{if $nav.register}}
	<a class="btn btn-warning btn-sm text-dark" href="{{$nav.register.0}}" title="{{$nav.register.3}}" id="{{$nav.register.4}}" >
		{{$nav.register.1}}
	</a>
	{{/if}}
</div>
{{/if}}



{{if $userinfo}}
<div class="dropdown usermenu">
	<div class="fakelink" data-toggle="dropdown">
		<img id="avatar" src="{{$userinfo.icon}}" alt="{{$userinfo.name}}">
		<i class="fa fa-caret-down"></i>
	</div>
	{{if $is_owner}}
	<div class="dropdown-menu">
		{{foreach $nav.usermenu as $usermenu}}
		<a class="dropdown-item{{if $usermenu.2}} active{{/if}}"  href="{{$usermenu.0}}" title="{{$usermenu.3}}" role="menuitem" id="{{$usermenu.4}}">{{$usermenu.1}}</a>
		{{/foreach}}
		{{if $nav.manage}}
		<a class="dropdown-item{{if $sel.active == Manage}} active{{/if}}" href="{{$nav.manage.0}}" title="{{$nav.manage.3}}" role="menuitem" id="{{$nav.manage.4}}">{{$nav.manage.1}}</a>
		{{/if}}	
		{{if $nav.channels}}
		{{foreach $nav.channels as $chan}}
		<a class="dropdown-item" href="manage/{{$chan.channel_id}}" title="{{$chan.channel_name}}" role="menuitem"><i class="fa fa-circle{{if $localuser == $chan.channel_id}} text-success{{else}} invisible{{/if}}"></i> {{$chan.channel_name}}</a>
		{{/foreach}}
		{{/if}}
		{{if $nav.profiles}}
		<a class="dropdown-item" href="{{$nav.profiles.0}}" title="{{$nav.profiles.3}}" role="menuitem" id="{{$nav.profiles.4}}">{{$nav.profiles.1}}</a>
		{{/if}}
		{{if $nav.settings}}
		<div class="dropdown-divider"></div>
		<a class="dropdown-item{{if $sel.active == Settings}} active{{/if}}" href="{{$nav.settings.0}}" title="{{$nav.settings.3}}" role="menuitem" id="{{$nav.settings.4}}">{{$nav.settings.1}}</a>
		{{/if}}
		{{if $nav.logout}}
		<div class="dropdown-divider"></div>
		<a class="dropdown-item" href="{{$nav.logout.0}}" title="{{$nav.logout.3}}" role="menuitem" id="{{$nav.logout.4}}">{{$nav.logout.1}}</a>
		{{/if}}
	</div>
	{{/if}}

	{{if ! $is_owner}}
	<div class="dropdown-menu" role="menu" aria-labelledby="avatar">
		<a class="dropdown-item" href="{{$nav.rusermenu.0}}" role="menuitem">{{$nav.rusermenu.1}}</a>
		<a class="dropdown-item" href="{{$nav.rusermenu.2}}" role="menuitem">{{$nav.rusermenu.3}}</a>
	</div>
	{{/if}}
</div>
{{/if}}



{{if $navbar_apps}}
<ul class="navbar-nav mr-auto d-none d-xl-flex">
{{foreach $navbar_apps as $navbar_app}}
<li>
{{$navbar_app}}
</li>
{{/foreach}}
</ul>
{{/if}}


<div class="navbar-toggler-right">

	{{if $nav.help.6}}
	<button id="context-help-btn" class="navbar-toggler border-0" type="button" onclick="contextualHelp(); return false;">
		<i class="fa fa-question-circle"></i>
	</button>
	{{/if}}

	<button id="expand-aside" type="button" class="navbar-toggler border-0" data-toggle="offcanvas" data-target="#region_1">
		<i class="fa fa-arrow-circle-right" id="expand-aside-icon"></i>
	</button>

	{{if $localuser || $nav.pubs}}
	<button id="notifications-btn" type="button" class="navbar-toggler border-0 text-white" data-toggle="collapse" data-target="#navbar-collapse-1">
		<i class="fa fa-exclamation"></i>
	</button>
	{{/if}}

	<button id="menu-btn" class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#navbar-collapse-2">
		<i class="fa fa-bars"></i>
	</button>
</div>





<div class="collapse navbar-collapse" id="navbar-collapse-1">

	<ul class="navbar-nav mr-auto">
		{{if $nav.network}}
		<li class="nav-item dropdown network-button" style="display: none;">
			<a class="nav-link" href="#" title="{{$nav.network.3}}" id="{{$nav.network.4}}" data-toggle="dropdown" rel="#navbar-network-menu">
				<i class="fa fa-fw fa-th"></i>
				<span class="badge badge-pill badge-secondary network-update"></span>
			</a>
			<div id="navbar-network-menu" class="dropdown-menu" rel="network">
				<a class="dropdown-item" id="nav-network-see-all" href="{{$nav.network.all.0}}">{{$nav.network.all.1}}</a>
				<a class="dropdown-item" id="nav-network-mark-all" href="#" onclick="markRead('network'); return false;">{{$nav.network.mark.1}}</a>
				{{$emptynotifications}}
			</div>
		</li>
		{{/if}}
		{{if $nav.home}}
		<li class="nav-item dropdown home-button" style="display: none;">
			<a class="nav-link" href="#" title="{{$nav.home.3}}" id="{{$nav.home.4}}" data-toggle="dropdown" rel="#navbar-home-menu">
				<i class="fa fa-fw fa-home"></i>
				<span class="badge badge-pill badge-danger home-update"></span>
			</a>
			<div id="navbar-home-menu" class="dropdown-menu" rel="home">
				<a class="dropdown-item" id="nav-home-see-all" href="{{$nav.home.all.0}}">{{$nav.home.all.1}}</a>
				<a class="dropdown-item" id="nav-home-mark-all" href="#" onclick="markRead('home'); return false;">{{$nav.home.mark.1}}</a>
				{{$emptynotifications}}
			</div>
		</li>
		{{/if}}
		{{if $nav.messages}}
		<li class="nav-item dropdown mail-button" style="display: none;">
			<a class="nav-link" href="#" title="{{$nav.messages.3}}" id="{{$nav.messages.4}}" data-toggle="dropdown" rel="#navbar-mail-menu">
				<i class="fa fa-fw fa-envelope"></i>
				<span class="badge badge-pill badge-danger mail-update"></span>
			</a>
			<div id="navbar-mail-menu" class="dropdown-menu" rel="messages">
				<a class="dropdown-item" id="nav-messages-see-all" href="{{$nav.messages.all.0}}">{{$nav.messages.all.1}}</a>
				<a class="dropdown-item" id="nav-messages-mark-all" href="#" onclick="markRead('messages'); return false;">{{$nav.messages.mark.1}}</a>
				{{$emptynotifications}}
			</div>
		</li>
		{{/if}}
		{{if $nav.all_events}}
		<li class="nav-item dropdown all_events-button" style="display: none;">
			<a class="nav-link" href="#" title="{{$nav.all_events.3}}" id="{{$nav.all_events.4}}" data-toggle="dropdown" rel="#navbar-all_events-menu">
				<i class="fa fa-fw fa-calendar"></i>
				<span class="badge badge-pill badge-secondary all_events-update"></span>
			</a>
			<div id="navbar-all_events-menu" class="dropdown-menu" rel="all_events">
				<a class="dropdown-item" id="nav-all_events-see-all" href="{{$nav.all_events.all.0}}">{{$nav.all_events.all.1}}</a>
				<a class="dropdown-item" id="nav-all_events-mark-all" href="#" onclick="markRead('all_events'); return false;">{{$nav.all_events.mark.1}}</a>
				{{$emptynotifications}}
			</div>
		</li>
		{{/if}}
		{{if $nav.intros}}
		<li class="nav-item dropdown intros-button" style="display: none;">
			<a class="nav-link" href="#" title="{{$nav.intros.3}}" id="{{$nav.intros.4}}" data-toggle="dropdown" rel="#navbar-intros-menu">
				<i class="fa fa-fw fa-users"></i>
				<span class="badge badge-pill badge-danger intros-update"></span>
			</a>
			<div id="navbar-intros-menu" class="dropdown-menu" rel="intros">
				<a class="dropdown-item" id="nav-intros-see-all" href="{{$nav.intros.all.0}}">{{$nav.intros.all.1}}</a>
				{{$emptynotifications}}
			</div>
		</li>
		{{/if}}
		{{if $nav.notifications}}
		<li class="nav-item dropdown notify-button" style="display: none;">
			<a class="nav-link" href="#" title="{{$nav.notifications.1}}" id="{{$nav.notifications.4}}" data-toggle="dropdown" rel="#navbar-notify-menu">
				<i class="fa fa-fw fa-exclamation"></i>
				<span class="badge badge-pill badge-danger notify-update"></span>
			</a>
			<div id="navbar-notify-menu" class="dropdown-menu" rel="notify">
				<a class="dropdown-item" id="nav-notify-see-all" href="{{$nav.notifications.all.0}}">{{$nav.notifications.all.1}}</a>
				<a class="dropdown-item" id="nav-notify-mark-all" href="#" onclick="markRead('notify'); return false;">{{$nav.notifications.mark.1}}</a>
				{{$emptynotifications}}
			</div>
		</li>
		{{/if}}

		{{if $nav.login && !$userinfo}}
		<li class="nav-item d-none d-xl-flex">
			{{if $nav.loginmenu.1.4}}
			<a class="nav-link" href="#" title="{{$nav.loginmenu.1.3}}" id="{{$nav.loginmenu.1.4}}" data-toggle="modal" data-target="#nav-login">
			{{$nav.loginmenu.1.1}}
			</a>
			{{else}}
			<a class="nav-link" href="login" title="{{$nav.loginmenu.1.3}}">
				{{$nav.loginmenu.1.1}}
			</a>
			{{/if}}
		</li>
		{{/if}}
		{{if $nav.register}}
		<li class="nav-item {{$nav.register.2}} d-none d-xl-flex">
			<a class="nav-link" href="{{$nav.register.0}}" title="{{$nav.register.3}}" id="{{$nav.register.4}}">{{$nav.register.1}}</a>
		</li>
		{{/if}}
		{{if $nav.alogout}}
		<li class="nav-item {{$nav.alogout.2}} d-none d-xl-flex">
			<a class="nav-link" href="{{$nav.alogout.0}}" title="{{$nav.alogout.3}}" id="{{$nav.alogout.4}}">{{$nav.alogout.1}}</a>
		</li>
		{{/if}}

	</ul>




	<div id="banner" class="navbar-text d-none d-xl-flex">{{$banner}}</div>


	<ul id="nav-right" class="navbar-nav ml-auto d-none d-xl-flex">
		<li class="nav-item collapse clearfix" id="nav-search">
			<form class="form-inline" method="get" action="search" role="search">
				<input class="form-control form-control-sm mt-1 mr-2" id="nav-search-text" type="text" value="" placeholder="&#xf002; {{$help}}" name="search" title="{{$nav.search.3}}" onclick="this.submit();" onblur="closeMenu('nav-search'); openMenu('nav-search-btn');"/>
			</form>
			<div id="nav-search-spinner" class="spinner-wrapper">
				<div class="spinner s"></div>
			</div>
		</li>
		<li class="nav-item" id="nav-search-btn">
			<a class="nav-link" href="#nav-search" title="{{$nav.search.3}}" onclick="openMenu('nav-search'); closeMenu('nav-search-btn'); $('#nav-search-text').focus(); return false;"><i class="fa fa-fw fa-search"></i></a>
		</li>
		{{if $nav.help.6}}
		<li class="nav-item dropdown {{$sel.help}}">
			<a class="nav-link {{$nav.help.2}}" target="hubzilla-help" href="{{$nav.help.0}}" title="{{$nav.help.3}}" id="{{$nav.help.4}}" onclick="contextualHelp(); return false;"><i class="fa fa-fw fa-question-circle"></i></a>
		</li>
		{{/if}}
		{{if $channel_apps.0}}
		<li class="nav-item dropdown" id="channel-menu">
			<a class="nav-link" href="#" data-toggle="dropdown"><img src="{{$channel_thumb}}" style="height:14px; width:14px;position:relative; top:-2px;" /></a>
			<div id="dropdown-menu" class="dropdown-menu dropdown-menu-right">
				{{foreach $channel_apps as $channel_app}}
				{{$channel_app}}
				{{/foreach}}
			</div>
		</li>
		{{/if}}
		<li class="nav-item dropdown" id="app-menu">
			<a class="nav-link" href="#" data-toggle="dropdown"><i class="fa fa-fw fa-bars"></i></a>
			<div id="dropdown-menu" class="dropdown-menu dropdown-menu-right">
				{{foreach $nav_apps as $nav_app}}
				{{$nav_app}}
				{{/foreach}}
				{{if $is_owner}}
				<div class="dropdown-divider"></div>
				<a class="dropdown-item" href="/apps"><i class="generic-icons-nav fa fa-fw fa-plus-circle"></i>{{$addapps}}</a>
				<a class="dropdown-item" href="/apporder"><i class="generic-icons-nav fa fa-fw fa-sort"></i>{{$orderapps}}</a>
				{{/if}}
			</div>
		</li>
	</ul>
</div>


<div class="collapse d-xl-none" id="navbar-collapse-2">
	<div class="navbar-nav mr-auto">
		{{if $channel_apps.0}}
		{{foreach $channel_apps as $channel_app}}
		{{$channel_app|replace:'dropdown-item':'nav-link'}}
		{{/foreach}}
		<div class="dropdown-header sys-apps-toggle" onclick="openClose('sys-apps-collapsed');">
			{{$sysapps_toggle}}
		</div>
		<div id="sys-apps-collapsed" style="display:none;">
		{{/if}}
		{{foreach $nav_apps as $nav_app}}
		{{$nav_app|replace:'dropdown-item':'nav-link'}}
		{{/foreach}}
		{{if $channel_apps.0}}
		</div>
		{{/if}}
		{{if $is_owner}}
		<div class="dropdown-divider"></div>
		<a class="nav-link" href="/apps"><i class="generic-icons-nav fa fa-fw fa-plus-circle"></i>{{$addapps}}</a>
		<a class="nav-link" href="/apporder"><i class="generic-icons-nav fa fa-fw fa-sort"></i>{{$orderapps}}</a>
		{{/if}}
	</div>
</div>
{{if $nav.help.6}}
<div id="contextual-help-content" class="contextual-help-content">
	{{$nav.help.5}}
	<div class="float-right">
		<a class="btn btn-primary btn-sm" target="hubzilla-help" href="{{$nav.help.0}}" title="{{$nav.help.3}}"><i class="fa fa-question"></i>&nbsp;{{$fulldocs}}</a>
		<a class="contextual-help-tool" href="#" onclick="contextualHelp(); return false;"><i class="fa fa-times"></i></a>
	</div>
</div>
{{/if}}
