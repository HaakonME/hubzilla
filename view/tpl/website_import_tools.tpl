<div id="website-import-tools" class="widget">
	<h3>{{$title}}</h3>
	<ul class="nav nav-pills nav-stacked">
		<li>
			<a href="#" onclick="openClose('import-form'); return false;"><i class="fa fa-cloud-upload generic-icons"></i> {{$import_label}}</a>
		</li>
		<form id="import-form" enctype="multipart/form-data" method="post" action="" style="display: none;" class="sub-menu">
            
            <input type="hidden" name="action" value="scan">
            
            <p class="descriptive-text">{{$file_import_text}}</p>
            <div class="form-group">
				 <div class="input-group">
                    <input class="widget-input" type="text" name="path" title="{{$hint}}" placeholder="{{$desc}}" />
                    <div class="input-group-btn">
                        <button class="btn btn-default btn-sm" type="submit" name="cloudsubmit" value="{{$select}}"><i class="fa fa-folder-open generic-icons"></i></button>
                    </div>
                </div>
			</div>
            
            <!-- Or upload a zipped file containing the website -->
            <p class="descriptive-text">{{$file_upload_text}}</p>
			<div class="form-group">
				<input id="website-upload-choose" type="file" name="zip_file" />
			</div>
			<button class="btn btn-primary btn-sm" type="submit" name="w_upload" value="w_upload">Upload</button>
		</form>
	</ul>
</div>
