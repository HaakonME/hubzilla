<ul class="dropdown-menu">
	{{foreach $apps as $app}}
	<li><a href="{{$app.url}}">{{$app.name}}</a></li>
	{{/foreach}}
</ul>
