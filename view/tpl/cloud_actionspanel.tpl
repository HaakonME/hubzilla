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
  <form id="ajax-upload-files" method="post" action="" enctype="multipart/form-data">
    <input type="hidden" name="sabreAction" value="put">
    <div>
      <div id="filedrag" style="height: 7em;"><br>{{$dragdroptext}}</div>
    </div>
    <div id="file-upload-list"></div>
    <div class="clear"></div>
    <label for="files-upload">{{$upload_header}}</label>
    <div class="clear"></div>
    <input class="form-group pull-left" id="files-upload" type="file" name="file" style="width: 70%;">
    <button class="btn btn-primary btn-sm pull-right" type="submit" value="{{$upload_submit}}">{{$upload_submit}}</button>
  </form>
  <div class="clear"></div>
  <hr/>
</div>
