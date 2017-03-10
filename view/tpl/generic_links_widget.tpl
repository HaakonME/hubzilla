<div class="widget{{if $class}} {{$class}}{{/if}}">
	{{if $title}}<h3>{{$title}}</h3>{{/if}}
	{{if $desc}}<div class="desc">{{$desc}}</div>{{/if}}
	
	<ul class="nav nav-pills flex-column">
		{{foreach $items as $item}}
		<li class="nav-item"><a href="{{$item.url}}" class="nav-link{{if $item.selected}} active{{/if}}">{{$item.label}}</a></li>
		{{/foreach}}
	</ul>
	
</div>
