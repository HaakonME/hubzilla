<div id="files-mkdir-tools" class="section-content-tools-wrapper">
  <label for="files-mkdir">{{$folder_header}}</label>
  <form method="post" action="">
    <input type="hidden" name="sabreAction" value="mkcol">
    <input id="files-mkdir" type="text" name="name" class="form-control form-group">
    <button class="btn btn-primary btn-sm pull-right" type="submit" value="{{$folder_submit}}">{{$folder_submit}}</button>
  </form>
  <div class="clear"></div>
</div>
<div id="files-upload-tools" class="section-content-tools-wrapper">
  {{if $quota.limit || $quota.used}}<div class="{{if $quota.warning}}section-content-danger-wrapper{{else}}section-content-info-wrapper{{/if}}">{{if $quota.warning}}<strong>{{$quota.warning}} </strong>{{/if}}{{$quota.desc}}</div>{{/if}}
  <form id="ajax-upload-files" method="post" action="" enctype="multipart/form-data"  class="acl-form" data-form_id="ajax-upload-files" data-allow_cid='{{$allow_cid}}' data-allow_gid='{{$allow_gid}}' data-deny_cid='{{$deny_cid}}' data-deny_gid='{{$deny_gid}}'>
    <input type="hidden" name="sabreAction" value="put">
    <label for="files-upload">{{$upload_header}}</label>
    <input class="form-group pull-left" id="files-upload" type="file" name="file">
    <button id="upload-submit" class="btn btn-primary btn-sm pull-right" type="submit" value="{{$upload_submit}}">{{$upload_submit}}</button>
  </form>
  <div class="clear"></div>
</div>
{{$aclselect}}
