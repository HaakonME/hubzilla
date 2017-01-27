<ul class="dropdown-menu">
	{{foreach $apps as $app}}
	<li><a href="{{$app.url}}">{{if $icon}}<i class="app-icon fa fa-{{$icon}}"></i>{{else}}<img src="{{$app.photo}}" width="16" height="16" />{{/if}}&nbsp;{{$app.name}}</a></li>
	{{/foreach}}
	<li class="divider"></li>
	<li><a href="/apps/edit"><i class="app-icon fa fa-pencil"></i>&nbsp;Edit Apps</a></li>
	<li><a href="/appman"><i class="app-icon fa fa-plus-circle"></i>&nbsp;Add App</a></li>
</ul>
