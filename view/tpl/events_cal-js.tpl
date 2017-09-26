{{$tabs}}
<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			<div class="btn-group">
				<button class="btn btn-outline-secondary btn-sm" onclick="changeView('prev', false);" title="{{$prev}}"><i class="fa fa-backward"></i></button>
					<button id="today-btn" class="btn btn-outline-secondary btn-sm" onclick="changeView('today', false);" title="{{$today}}"><div id="events-spinner" class="spinner s"></div><i class="fa fa-bullseye" style="display: none; width: 1rem;"></i></button>
				<button class="btn btn-outline-secondary btn-sm" onclick="changeView('next', false);" title="{{$next}}"><i class="fa fa-forward"></i></button>
			</div>
			<button id="fullscreen-btn" type="button" class="btn btn-outline-secondary btn-sm" onclick="makeFullScreen();"><i class="fa fa-expand"></i></button>
			<button id="inline-btn" type="button" class="btn btn-outline-secondary btn-sm" onclick="makeFullScreen(false);"><i class="fa fa-compress"></i></button>
		</div>
		<h2 id="title"></h2>
		<div class="clear"></div>
	</div>
	<div class="clear"></div>
	<div class="section-content-wrapper-np">
		<div id="events-calendar"></div>
	</div>
</div>
