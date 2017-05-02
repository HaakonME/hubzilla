<div id="wiki_list" class="widget">
	<h3>{{$header}}</h3>
	<ul class="nav nav-pills flex-column">
		{{foreach $wikis as $wiki}}
		<li class="nav-item"><a class="nav-link" href="/wiki/{{$channel}}/{{$wiki.urlName}}/Home" title="{{$view}}">{{$wiki.title}}</a></li> 
		{{/foreach}}
	</ul>
</div>
