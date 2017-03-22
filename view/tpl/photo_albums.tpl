<div id="side-bar-photos-albums" class="widget">
	<h3>{{$title}}</h3>
	<ul class="nav nav-pills nav-stacked">
		<li><a href="{{$baseurl}}/photos/{{$nick}}" title="{{$title}}" >{{$recent_photos}}</a></li>
		{{if $albums}}
		{{foreach $albums as $al}}
		{{if $al.shorttext}}
		<li><a href="{{$baseurl}}/photos/{{$nick}}/album/{{$al.bin2hex}}"><span class="badge pull-right">{{$al.total}}</span>{{$al.shorttext}}</a></li>
		{{/if}}
		{{/foreach}}
		{{/if}}
	</ul>
</div>
