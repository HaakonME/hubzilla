<div id="website-portation-tools" class="widget">
	<div class="nav nav-pills flex-column">
		<a class="nav-link"  href="#" onclick="openClose('import-form'); return false;"><i class="fa fa-cloud-upload generic-icons"></i> {{$import_label}}</a>
		<div id="import-form" class="sub-menu-wrapper">
			<div class="sub-menu">
				<form enctype="multipart/form-data" method="post" action="">
					<input type="hidden" name="action" value="scan">
					<p class="descriptive-text">{{$file_import_text}}</p>
					<div class="form-group">
						<input class="form-control" type="text" name="path" title="{{$hint}}" placeholder="{{$desc}}" />
					</div>
					<div class="form-group">
						<button class="btn btn-primary btn-sm" type="submit" name="cloudsubmit" value="{{$select}}">Submit</button>
					</div>
					<!-- Or upload a zipped file containing the website -->
					<p class="descriptive-text">{{$file_upload_text}}</p>
					<div class="form-group">
						<input class="form-control-file w-100" type="file" name="zip_file" />
					</div>
					<div class="form-group">
						<button class="btn btn-primary btn-sm" type="submit" name="w_upload" value="w_upload">Submit</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<div class="nav nav-pills flex-column">
		<a class="nav-link" href="#" onclick="openClose('export-form'); openClose('export-cloud-form'); return false;"><i class="fa fa-share-square-o generic-icons"></i> {{$export_label}}</a>
		<div id="export-form" class="sub-menu-wrapper">
			<div class="sub-menu">
				<form enctype="multipart/form-data" method="post" action="">
					<input type="hidden" name="action" value="exportzipfile">
					<!-- Or download a zipped file containing the website -->
					<p class="descriptive-text">{{$file_download_text}}</p>
					<div class="form-group">
						<input class="form-control" type="text" name="zipfilename" title="{{$filename_hint}}" placeholder="{{$filename_desc}}" value="" />
					</div>
					<div class="form-group">
						<button class="btn btn-primary btn-sm" type="submit" name="w_download" value="w_download">Submit</button>
					</div>
				</form>
			</div>
		</div>
		<div id="export-cloud-form" class="sub-menu-wrapper">
			<div class="sub-menu">
				<form enctype="multipart/form-data" method="post" action="">
					<input type="hidden" name="action" value="exportcloud">
					<!-- Or export the website elements to a cloud files folder -->
					<p style="margin-top: 10px;" class="descriptive-text">{{$cloud_export_text}}</p>
					<div class="form-group">
						<input class="form-control" type="text" name="exportcloudpath" title="{{$cloud_export_hint}}" placeholder="{{$cloud_export_desc}}" />
					</div>
					<div class="form-group">
						<button class="btn btn-primary btn-sm" type="submit" name="exportcloudsubmit" value="{{$cloud_export_select}}">Submit</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
