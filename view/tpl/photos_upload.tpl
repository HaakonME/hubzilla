<div id="photo-upload-form">
	<div class="section-content-tools-wrapper">
		<form action="photos/{{$nickname}}" enctype="multipart/form-data" method="post" name="photos-upload-form" id="photos-upload-form" class="acl-form" data-form_id="photos-upload-form" data-allow_cid='{{$allow_cid}}' data-allow_gid='{{$allow_gid}}' data-deny_cid='{{$deny_cid}}' data-deny_gid='{{$deny_gid}}'>
			<input type="hidden" id="photos-upload-source" name="source" value="photos" />

			<div class="form-group">
				<label for="photos-upload-album">{{$newalbum_label}}</label>
				<input type="text" class="form-control" id="photos-upload-album" name="newalbum" placeholder="{{$newalbum_placeholder}}" value="{{$selname}}" list="dl-photo-upload">
				<datalist id="dl-photo-upload">
				{{foreach $albums as $al}}
					{{if $al.text}}
					<option value="{{$al.text}}" />
					{{/if}}
				{{/foreach}}
				</datalist>
			</div>
			{{if $default}}
			<div class="form-group">
				<input id="photos-upload-choose" type="file" name="userfile" />
			</div>
			{{include file="field_input.tpl" field=$caption}}
			{{include file="field_checkbox.tpl" field=$visible}}
			<div id="body-textarea">
			{{include file="field_textarea.tpl" field=$body}}
			</div>
			<div class="pull-right btn-group">
				<div class="btn-group">
					{{if $lockstate}}
					<button id="dbtn-acl" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" onclick="return false;">
						<i id="jot-perms-icon" class="fa fa-{{$lockstate}}"></i>
					</button>
					{{/if}}
					<button id="dbtn-submit" class="btn btn-primary btn-sm" type="submit" name="submit" >{{$submit}}</button>
				</div>

			</div>
			{{/if}}
			<div class="clear"></div>

			{{if $uploader}}
			{{include file="field_input.tpl" field=$caption}}
			{{include file="field_checkbox.tpl" field=$visible}}
			<div id="body-textarea">
			{{include file="field_textarea.tpl" field=$body}}
			</div>
			<div id="photos-upload-perms" class="btn-group pull-right">
				{{if $lockstate}}
				<button class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" onclick="return false;">
					<i id="jot-perms-icon" class="fa fa-{{$lockstate}}"></i>
				</button>
				{{/if}}
				<div class="pull-right">
					{{$uploader}}
				</div>
			</div>
			{{/if}}
		</form>
	</div>
	{{$aclselect}}
	<div id="photos-upload-end" class="clear"></div>
</div>
