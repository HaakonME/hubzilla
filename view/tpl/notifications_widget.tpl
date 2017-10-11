<style>
	.notification-content {
		max-height: 70vh;
		overflow: auto;
	}

	.notification-content.collapsing {
		overflow: hidden;
	}

	.fs {
		position: fixed;
		top: 0px;
		left: 0px;
		padding: 4.5rem .5rem 1rem .5rem;
		background-color: white;
		width: 100%;
		max-width: 100%;
		height: 100%;
		z-index: 1029;
		overflow: auto;
	}

	#notifications {
		margin-bottom: 1rem;
	}
</style>

{{if $notifications}}
<div id="notifications_wrapper">
	<div id="notifications" class="navbar-nav" data-children=".nav-item">
		<div id="nav-notifications-template" rel="template">
			<a class="list-group-item clearfix notification {5}" href="{0}" title="{2} {3}">
				<img class="menu-img-3" data-src="{1}">
				<span class="contactname">{2}</span>
				<span class="dropdown-sub-text">{3}<br>{4}</span>
			</a>
		</div>
		{{foreach $notifications as $notification}}
		<div class="collapse {{$notification.type}}-button">
			<a class="list-group-item" href="#nav-{{$notification.type}}-menu" title="{{$notification.title}}" data-toggle="collapse" data-parent="#notifications" rel="#nav-{{$notification.type}}-menu">
				<i class="fa fa-fw fa-{{$notification.icon}}"></i> {{$notification.label}}
				<span class="float-right badge badge-{{$notification.severity}} {{$notification.type}}-update"></span>
			</a>
			<div id="nav-{{$notification.type}}-menu" class="collapse notification-content" rel="{{$notification.type}}">
				{{if $notification.viewall}}
				<a class="list-group-item text-dark" id="nav-{{$notification.type}}-see-all" href="{{$notification.viewall.url}}">
					<i class="fa fa-fw fa-external-link"></i> {{$notification.viewall.label}}
				</a>
				{{/if}}
				{{if $notification.markall}}
				<a class="list-group-item text-dark" id="nav-{{$notification.type}}-mark-all" href="{{$notification.markall.url}}" onclick="markRead('{{$notification.type}}'); return false;">
					<i class="fa fa-fw fa-check"></i> {{$notification.markall.label}}
				</a>
				{{/if}}
				{{$loading}}
			</div>
		</div>
		{{/foreach}}
	</div>
</div>
{{/if}}
