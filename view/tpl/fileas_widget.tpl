<div id="fileas-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<div id="nets-desc">{{$desc}}</div>
	
	<ul class="nav nav-pills flex-column">
		<li class="nav-item"><a class="nav-link" href="{{$base}}"{{if $sel_all}} class="fileas-selected"{{/if}}>{{$all}}</a></li>
		{{foreach $terms as $term}}
		<li class="nav-item"><a class="nav-link" href="{{$base}}?f=&file={{$term.name}}"{{if $term.selected}} class="fileas-selected"{{/if}}>{{$term.name}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
