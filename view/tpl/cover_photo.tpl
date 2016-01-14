<div class="generic-content-wrapper-styled">
<h1>{{$title}}</h1>

<form enctype="multipart/form-data" action="cover_photo" method="post">
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

<div id="profile-photo-upload-wrapper">

<label id="profile-photo-upload-label" class="form-label" for="profile-photo-upload">{{$lbl_upfile}}</label>
<input name="userfile" class="form-input" type="file" id="profile-photo-upload" size="48" />
<div class="clear"></div>

<div id="profile-photo-submit-wrapper">
<input type="submit" name="submit" id="profile-photo-submit" value="{{$submit}}">
</div>
</div>

</form>

<div id="profile-photo-link-select-wrapper">
{{$select}}
</div>
</div>
