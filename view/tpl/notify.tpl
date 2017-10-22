<div class="mb-4 notif-item">
	{{if ! $item_seen}}
	<span class="float-right badge badge-pill badge-success text-uppercase">{{$new}}</span>
	{{/if}}
	<a href="{{$item_link}}">
		<img src="{{$item_image}}" class="menu-img-3">
		<span class="{{if $item_seen}}text-muted{{/if}}">{{$item_text}}</span><br>
		<span class="dropdown-sub-text">{{$item_when}}</span>
	</a>
</div>
