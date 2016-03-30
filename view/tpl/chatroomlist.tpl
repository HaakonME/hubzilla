<div class="widget">
	<h3>{{$header}}</h3>
	{{if $items}}
	<ul class="nav nav-pills nav-stacked">
		{{foreach $items as $item}}
		<li><a href="{{$baseurl}}/chat/{{$nickname}}/{{$item.cr_id}}"><span class="badge pull-right">{{$item.cr_inroom}}</span>{{$item.cr_name}}</a></li>
		{{/foreach}}
	</ul>
	{{/if}}
</div>

