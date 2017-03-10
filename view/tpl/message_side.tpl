<div class="widget">
	<h3>{{$title}}</h3>
	<ul class="nav nav-pills flex-column">
		<li class="nav-item"><a href="{{$combined.url}}" class="nav-link{{if $combined.sel}} active{{/if}}">{{$combined.label}}</a></li>
		<li class="nav-item"><a href="{{$inbox.url}}" class="nav-link{{if $inbox.sel}} active{{/if}}">{{$inbox.label}}</a></li>
		<li class="nav-item"><a href="{{$outbox.url}}" class="nav-link{{if $outbox.sel}} active{{/if}}">{{$outbox.label}}</a></li>
		<li class="nav-item"><a href="{{$new.url}}" class="nav-link{{if $new.sel}} active{{/if}}">{{$new.label}}</a></li>
	</ul>
</div>
