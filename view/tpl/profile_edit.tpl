<div class="generic-content-wrapper">

<div class="section-title-wrapper"><h2>{{$banner}}</h2></div>

<div class="section-content-wrapper">
<form id="profile-edit-form" name="form1" action="profiles/{{$profile_id}}" enctype="multipart/form-data" method="post" >

<div id="profile-edit-links">
<span class="btn btn-default"><a href="profile_photo" id="profile-photo_upload-link" title="{{$profpic}}">{{$profpic}}</a></span>
{{if $is_default}}<span class="btn btn-default"><a href="cover_photo" id="cover-photo_upload-link" title="{{$coverpic}}">{{$coverpic}}</a></span>{{/if}}
<span class="btn btn-default"><a href="profile/{{$profile_id}}/view" id="profile-edit-view-link" title="{{$viewprof}}">{{$viewprof}}</a></span>
{{if ! $is_default}}<span class="btn btn-default"><a href="profperm/{{$profile_id}}" id="profile-edit-view-link" title="{{$editvis}}">{{$editvis}}</a></span>{{/if}}
{{if $profile_clone_link}}<span class="btn btn-default"><a href="{{$profile_clone_link}}" id="profile-edit-clone-link" title="{{$cr_prof}}">{{$cl_prof}}</a></span>{{/if}}
{{if $exportable}}<br /><span class="btn btn-default"><a href="profiles/export/{{$profile_id}}" target="_blank">{{$lbl_export}}</a></span>
<span class="btn btn-default profile-import"><b>{{$lbl_import}}</b> <input type="file" name="userfile" class="profile-import" ></span>
{{/if}}
{{if ! $is_default}}<span class="btn btn-danger"><a href="{{$profile_drop_link}}" id="profile-edit-drop-link" title="{{$del_prof}}" onclick="return confirmDelete();" {{$disabled}} >{{$del_prof}}</a></span>{{/if}}
</div>


<div class="clear"></div>

{{if $is_default}}
<div class="section-content-info-wrapper">{{$default}}</div>
{{/if}}


<div id="profile-edit-wrapper" >
<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>

{{include file="field_input.tpl" field=$profile_name}}

{{include file="field_input.tpl" field=$name}}

{{if $fields.pdesc}}
{{include file="field_input.tpl" field=$pdesc}}
{{/if}}

{{if $fields.gender}}
<div id="profile-edit-gender-wrapper" class="form-group field select" >
<label id="profile-edit-gender-label" for="gender-select" >{{$lbl_gender}}</label>
{{if $advanced}}
{{$gender}}
{{else}}
{{$gender_min}}
{{/if}}
</div>
<div class="clear"></div>
{{/if}}

{{if $fields.dob}}
{{$dob}}
{{/if}}

{{include file="field_checkbox.tpl" field=$hide_friends}}

<div class="form-group" >
<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
</div>
<div class="clear"></div>

{{if $fields.address}}
{{include file="field_input.tpl" field=$address}}
{{/if}}

{{if $fields.locality}}
{{include file="field_input.tpl" field=$locality}}
{{/if}}

{{if $fields.postal_code}}
{{include file="field_input.tpl" field=$postal_code}}
{{/if}}

{{if $fields.region}}
{{include file="field_input.tpl" field=$region}}
{{/if}}

{{if $fields.country_name}}
{{include file="field_input.tpl" field=$country_name}}
{{/if}}

{{if $fields.hometown}}
{{include file="field_input.tpl" field=$hometown}}
{{/if}}

<div class="form-group" >
<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
</div>
<div class="clear"></div>

{{if $fields.marital }}
<div id="profile-edit-marital-wrapper" class="form-group field" >
<label id="profile-edit-marital-label" for="profile-edit-marital" ><span class="heart">&hearts;</span>&nbsp;{{$lbl_marital}}</label>
{{if $advanced}}
{{$marital}}
{{else}}
{{$marital_min}}
{{/if}}
</div>
<div class="clear"></div>

{{if $fields.with}}
{{include file="field_input.tpl" field=$with}}
{{/if}}

{{if $fields.howlong}}
{{include file="field_input.tpl" field=$howlong}}
{{/if}}

<div class="clear"></div>
{{/if}}

{{if $fields.homepage}}
{{include file="field_input.tpl" field=$homepage}}
{{/if}}

{{if $fields.sexual}}
<div id="profile-edit-sexual-wrapper" class="form-group field" >
<label id="profile-edit-sexual-label" for="sexual-select" >{{$lbl_sexual}}</label>
{{$sexual}}
</div>
<div class="clear"></div>
{{/if}}

{{if $fields.politic}}
{{include file="field_input.tpl" field=$politic}}
{{/if}}

{{if $fields.religion}}
{{include file="field_input.tpl" field=$religion}}
{{/if}}

{{if $fields.keywords}}
{{include file="field_input.tpl" field=$keywords}}
{{/if}}

{{if $fields.about}}
{{include file="field_textarea.tpl" field=$about}}
{{/if}}

{{if $fields.contact}}
{{include file="field_textarea.tpl" field=$contact}}
{{/if}}


{{if $fields.interest}}
{{include file="field_textarea.tpl" field=$interest}}
{{/if}}

{{if $fields.likes}}
{{include file="field_textarea.tpl" field=$likes}}
{{/if}}

{{if $fields.dislikes}}
{{include file="field_textarea.tpl" field=$dislikes}}
{{/if}}

{{if $fields.channels}}
{{include file="field_textarea.tpl" field=$channels}}
{{/if}}

{{if $fields.music}}
{{include file="field_textarea.tpl" field=$music}}
{{/if}}

{{if $fields.book}}
{{include file="field_textarea.tpl" field=$book}}
{{/if}}

{{if $fields.tv}}
{{include file="field_textarea.tpl" field=$tv}}
{{/if}}

{{if $fields.film}}
{{include file="field_textarea.tpl" field=$film}}
{{/if}}


{{if $fields.romance}}
{{include file="field_textarea.tpl" field=$romance}}
{{/if}}

{{if $fields.work}}
{{include file="field_textarea.tpl" field=$work}}
{{/if}}

{{if $fields.education}}
{{include file="field_textarea.tpl" field=$education}}
{{/if}}

{{if $extra_fields}}
{{foreach $extra_fields as $field }}
{{include file="field_input.tpl" field=$field}}
{{/foreach}}
{{/if}}

<div class="form-group" >
<button type="submit" name="submit" class="btn btn-primary" value="{{$submit}}">{{$submit}}</button>
</div>
<div class="clear"></div>

</form>
</div>
</div>
</div>

