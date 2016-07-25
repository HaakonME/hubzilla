<div id="files-mkdir-tools" class="section-content-tools-wrapper">
  <label for="files-mkdir">{{$folder_header}}</label>
  <form method="post" action="">
    <input type="hidden" name="sabreAction" value="mkcol">
    <input id="files-mkdir" type="text" name="dirname" class="form-control form-group">
    <button class="btn btn-primary btn-sm pull-right" type="submit" value="{{$folder_submit}}">{{$folder_submit}}</button>
  </form>
  <div class="clear"></div>
</div>
<div id="files-upload-tools" class="section-content-tools-wrapper">
  {{if $quota.limit || $quota.used}}<div class="{{if $quota.warning}}section-content-danger-wrapper{{else}}section-content-info-wrapper{{/if}}">{{if $quota.warning}}<strong>{{$quota.warning}} </strong>{{/if}}{{$quota.desc}}</div>{{/if}}
  <form id="ajax-upload-files" method="post" action="file_upload" enctype="multipart/form-data">
	<input type="hidden" name="channick" value="{{$channick}}" />
	<input type="hidden" name="return_url" value="{{$return_url}}" />
    <div>
	<div id="filedrag" style="height: 7em;"><br>{{$dragdroptext}}</div>
    </div>
    <div id="file-upload-list"></div>
    <div class="clear"></div>
    {{$aclselect}}
    <label for="files-upload">{{$upload_header}}</label>
    <div class="clear"></div>
    <input class="form-group pull-left" id="files-upload" type="file" name="userfile" style="width: 70%;">
	<div class="pull-right btn-group">
		<div class="btn-group">
			{{if $lockstate}}
			<button id="dbtn-acl" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" onclick="return false;">
				<i id="jot-perms-icon" class="fa fa-{{$lockstate}}"></i>
			</button>
			{{/if}}
			<button id="dbtn-submit" class="btn btn-primary btn-sm pull-right" type="submit" name="submit" value="{{$upload_submit}}">{{$upload_submit}}</button>
		</div>
	</div>
  </form>
  <div class="clear"></div>
  <hr/>
</div>

