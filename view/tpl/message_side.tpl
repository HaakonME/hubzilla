<div class="widget">
	<h3>{{$title}}</h3>
	<ul class="nav nav-pills nav-stacked">
		<li><a href="{{$check.url}}"{{if $check.sel}} class="active"{{/if}}>{{$check.label}}</a></li>
	{{*
		<li><a href="{{$combined.url}}"{{if $combined.sel}} class="active"{{/if}}>{{$combined.label}}</a></li>
		<li><a href="{{$inbox.url}}"{{if $inbox.sel}} class="active"{{/if}}>{{$inbox.label}}</a></li>
		<li><a href="{{$outbox.url}}"{{if $outbox.sel}} class="active"{{/if}}>{{$outbox.label}}</a></li>
	*}}
		<li><a href="{{$new.url}}"{{if $new.sel}} class="active"{{/if}}>{{$new.label}}</a></li>
	</ul>
	{{if $tabs}}
	<ul class="nav nav-pills nav-stacked">
		{{foreach $tabs as $t}}
			<li><a href="{{$t.url}}"{{if $t.sel}} class="active"{{/if}}>{{$t.label}}</a></li>
		{{/foreach}}
	</ul>
	{{/if}}
</div>
