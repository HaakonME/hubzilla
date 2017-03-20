<div class="mb-4 d-none d-md-block">
	<ul class="nav nav-tabs nav-fill">
		{{foreach $tabs as $tab}}
		<li class="nav-item"{{if $tab.id}} id="{{$tab.id}}"{{/if}}><a class="nav-link{{if $tab.sel}} {{$tab.sel}}{{/if}}" href="{{$tab.url}}"{{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a></li>
		{{/foreach}}
	</ul>
</div>
<div class="d-md-none dropdown clearfix" style="position:fixed; right:7px; top:4.5rem; z-index:1020">
	<button type="button" class="btn btn-outline-secondary btn-sm float-right" data-toggle="dropdown">
		<i class="fa fa-bars"></i>
	</button>
	<div class="dropdown-menu dropdown-menu-right">
		{{foreach $tabs as $tab}}
		<a class="dropdown-item{{if $tab.sel}} {{$tab.sel}}{{/if}}" href="{{$tab.url}}"{{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a>
		{{/foreach}}
	</div>
</div>
