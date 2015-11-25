<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			<button class="btn btn-default btn-xs" onclick="openClose('event-tools'); closeMenu('form');"><i class="icon-cog"></i></button>
			<button class="btn btn-success btn-xs" onclick="openClose('form'); closeMenu('event-tools');">{{$new_event.1}}</button>

			<div class="btn-group">
				<button class="btn btn-default btn-xs" onclick="changeView('prev', false);" title="{{$prev}}"><i class="icon-backward"></i></button>
				<button class="btn btn-default btn-xs" onclick="changeView('today', false);" title="{{$today}}"><i class="icon-bullseye"></i></button>
				<button class="btn btn-default btn-xs" onclick="changeView('next', false);" title="{{$next}}"><i class="icon-forward"></i></button>
			</div>
		</div>
		<h2 id="title"></h2>
		<div class="clear"></div>
	</div>
	<div id="form" class="section-content-tools-wrapper"{{if !$expandform}} style="display:none;"{{/if}}>
		{{$form}}
	</div>
	<div id="event-tools" class="section-content-tools-wrapper" style="display:none;">
		<div class="form-group">
			<button class="btn btn-primary btn-xs" onclick="exportDate(); return false;"><i class="icon-download"></i>&nbsp;{{$export.1}}</button>
			<button class="btn btn-primary btn-xs" onclick="openClose('event-upload-form');"><i class="icon-upload"></i>&nbsp;{{$upload}}</button>
		</div>
		<div id="event-upload-form" style="display:none;">
			<form action="events" enctype="multipart/form-data" method="post" name="event-upload-form" id="event-upload-form">
				<button id="dbtn-submit" class="btn btn-primary btn-sm pull-right" type="submit" name="submit" >{{$submit}}</button>
				<input id="event-upload-choose" type="file" name="userfile" />

			</form>
		</div>
	</div>
	<div class="clear"></div>
	<div class="section-content-wrapper-np">
		<div id="events-calendar"></div>
	</div>
</div>
