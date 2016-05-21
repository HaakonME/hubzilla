<div id="wiki_list" class="widget">
	<h3>{{$header}}</h3>
	<ul class="nav nav-pills nav-stacked">
		{{foreach $read as $r}}
		<li><a href="">{{$r}}</a></li>
		{{/foreach}}
		{{foreach $write as $r}}
		<li><a href="">{{$r}}</a></li>
		{{/foreach}}
	</ul>
</div>

