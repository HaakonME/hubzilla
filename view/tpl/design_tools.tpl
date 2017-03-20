<div id="design-tools" class="widget design-tools">
	<h3>{{$title}}</h3>
	<div class="nav nav-pills flex-column">
		<a class="nav-link" href="blocks/{{$who}}">{{$blocks}}</a>
		<a class="nav-link" href="menu{{if $sys}}?f=&sys=1{{/if}}">{{$menus}}</a>
		<a class="nav-link" href="layouts/{{$who}}">{{$layout}}</a>
		<a class="nav-link" href="webpages/{{$who}}">{{$pages}}</a>
	</div>
</div>
