<div class="widget">
	<h3>{{$title}}</h3>
	<ul class="nav nav-pills flex-column">
		<li class="nav-item"><a class="nav-link" href="#" onclick="exportDate(); return false;"><i class="fa fa-arrow-circle-o-down"></i>&nbsp;{{$export}}</a></li>
		<li class="nav-item"><a class="nav-link" href="#" onclick="openClose('event-upload-form'); return false;"><i class="fa fa-arrow-circle-o-up"></i>&nbsp;{{$import}}</a></li>
	</ul>
	<div id="event-upload-form" class="sub-menu-wrapper">
		<div class="sub-menu">
			<form action="events" enctype="multipart/form-data" method="post" name="event-upload-form" id="event-upload-form">
				<div class="form-group">
					<input id="event-upload-choose" class="form-control-file w-100" type="file" name="userfile" />
				</div>
				<button id="dbtn-submit" class="btn btn-primary btn-sm" type="submit" name="submit" >{{$submit}}</button>
			</form>
		</div>
	</div>
</div>
