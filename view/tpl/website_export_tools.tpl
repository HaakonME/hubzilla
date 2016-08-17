<div id="website-export-tools" class="widget">
		<h3>{{$title}}</h3>
		<ul class="nav nav-pills nav-stacked">
				<li>
						<a href="#" onclick="openClose('export-form');
					return false;"><i class="fa fa-cloud-upload generic-icons"></i> {{$export_label}}</a>
				</li>
				<li>
						<form id="export-form" enctype="multipart/form-data" method="post" action="" style="display: none;" class="sub-menu">

						</form>
				</li>
		</ul>
</div>
