<style>
	#notifications {
		width: 100%;
	}

	.notification-content {
		max-height: 50vh;
		overflow: auto;
		border-left: 0.2rem solid #eee;
	}

	.fs {
		position: fixed;
		top: 0px;
		left: 0px;
		display: block !important;
		background-color: white;
		width: 100%;
		max-width: 100%;
		height: 100vh;
		z-index: 1020;
	}

	.fs #notifications {
		position: relative !important;
		width: 100% !important;
		top: 0px !important;
	}
</style>

<div id="nav-notifications-template" rel="template">
	<a class="dropdown-item clearfix dropdown-notification {5}" href="{0}" title="{2} {3}">
		<img class="menu-img-3" data-src="{1}">
		<span class="contactname">{2}</span>
		<span class="dropdown-sub-text">{3}<br>{4}</span>
	</a>
</div>

<ul id="notifications" class="navbar-nav" style="position: fixed; width: 280px; top: 64px;" data-children=".nav-item">
	{{foreach $notifications as $notification}}
	<li class="nav-item {{$notification.type}}-button" style="display: none;">
		<a class="nav-link" href="#nav-{{$notification.type}}-menu" title="{{$notification.title}}" data-toggle="collapse" data-parent="#notifications" rel="#nav-{{$notification.type}}-menu">
			<i class="fa fa-fw fa-{{$notification.icon}}"></i> {{$notification.label}}
			<span class="float-right badge badge-{{$notification.severity}} {{$notification.type}}-update"></span>
		</a>
		<div id="nav-{{$notification.type}}-menu" class="collapse notification-content" rel="{{$notification.type}}">
			{{if $notification.viewall}}
			<a class="dropdown-item" id="nav-{{$notification.type}}-see-all" href="{{$notification.viewall.url}}">{{$notification.viewall.label}}</a>
			{{/if}}
			{{if $notification.markall}}
			<a class="dropdown-item" id="nav-{{$notification.type}}-mark-all" href="{{$notification.markall.url}}" onclick="markRead('{{$notification.type}}'); return false;">{{$notification.markall.label}}</a>
			{{/if}}
			{{$loading}}
		</div>
	</li>
	{{/foreach}}
</ul>
