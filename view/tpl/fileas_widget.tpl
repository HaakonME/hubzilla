<div id="fileas-sidebar" class="widget">
	<h3>{{$title}}</h3>
	<div id="nets-desc">{{$desc}}</div>
	
	<ul class="nav nav-pills flex-column">
		<li class="nav-item"><a class="nav-link{{if $sel_all}} active{{/if}}" href="{{$base}}">{{$all}}</a></li>
		{{foreach $terms as $term}}
		<li class="nav-item"><a class="nav-link{{if $term.selected}} active{{/if}}" href="{{$base}}?f=&file={{$term.name}}">{{$term.name}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
