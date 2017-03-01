<div id="follow-sidebar" class="widget">
	<h3>{{$connect}}</h3>
	<form action="follow" method="post" />
		<div class="input-group">
			<input class="form-control input-sm" type="text" name="url" title="{{$hint}}" placeholder="{{$desc}}" />
			<div class="input-group-btn">
				<button class="btn btn-success btn-sm" type="submit" name="submit" value="{{$follow}}"><i class="fa fa-fw fa-plus"></i></button>
			</div>
		</div>
	</form>
	{{if $abook_usage_message}}
	<div class="usage-message" id="abook-usage-message">{{$abook_usage_message}}</div>
	{{/if}}
</div>
