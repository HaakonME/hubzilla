<div class="generic-content-wrapper">
	<div class="section-title-wrapper clearfix">
		{{if $notifications_available}}
		<a href="#" class="btn btn-outline-secondary btn-sm float-right" onclick="markRead('notify'); setTimeout(function() { window.location.href=window.location.href; },1500); return false;">{{$notif_link_mark_seen}}</a>
		{{/if}}
		<h2>{{$notif_header}}</h2>
	</div>
	<div class="section-content-wrapper">
		{{$notif_content}}
	</div>
</div>
