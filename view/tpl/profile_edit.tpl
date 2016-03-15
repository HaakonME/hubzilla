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


<div id="profile-edit-links-end"></div>

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
<label id="profile-edit-gender-label" for="gender-select" >{{$lbl_gender}} </label>
{{if $advanced}}
{{$gender}}
{{else}}
{{$gender_min}}
{{/if}}
</div>
<div id="profile-edit-gender-end"></div>
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
<div id="profile-edit-homepage-wrapper" class="form-group field" >
<label id="profile-edit-homepage-label" for="profile-edit-homepage" >{{$lbl_homepage}} </label>
<input type="text" class="form-control" size="32" name="homepage" id="profile-edit-homepage" value="{{$homepage}}" />
</div>
<div id="profile-edit-homepage-end"></div>
{{/if}}

{{if $fields.sexual}}
<div id="profile-edit-sexual-wrapper" class="form-group field" >
<label id="profile-edit-sexual-label" for="sexual-select" >{{$lbl_sexual}} </label>
{{$sexual}}
</div>
<div id="profile-edit-sexual-end"></div>
{{/if}}

{{if $fields.politic}}
<div id="profile-edit-politic-wrapper" class="form-group field" >
<label id="profile-edit-politic-label" for="profile-edit-politic" >{{$lbl_politic}} </label>
<input class="form-control" type="text" size="32" name="politic" id="profile-edit-politic" value="{{$politic}}" />
</div>
<div id="profile-edit-politic-end"></div>
{{/if}}

{{if $fields.religion}}
<div id="profile-edit-religion-wrapper" class="form-group field" >
<label id="profile-edit-religion-label" for="profile-edit-religion" >{{$lbl_religion}} </label>
<input type="text" class="form-control" size="32" name="religion" id="profile-edit-religion" value="{{$religion}}" />
</div>
<div id="profile-edit-religion-end"></div>
{{/if}}

{{if $fields.keywords}}
<div id="profile-edit-pubkeywords-wrapper" class="form-group field" >
<label id="profile-edit-pubkeywords-label" for="profile-edit-pubkeywords" >{{$lbl_pubkey}} </label>
<input type="text" class="form-control" size="32" name="keywords" id="profile-edit-pubkeywords" title="{{$lbl_ex2}}" value="{{$keywords}}" />
</div>
<span class="help-block">{{$lbl_pubdsc}}</span>
<div id="profile-edit-pubkeywords-end"></div>
{{/if}}

<br /><br />

{{if $fields.about}}
<div id="about-jot-wrapper" class="form-group field" >
<p id="about-jot-desc" >
{{$lbl_about}}
</p>

<textarea class="form-control" rows="10" cols="72" id="profile-about-text" name="about" >{{$about}}</textarea>

</div>
<div id="about-jot-end"></div>
{{/if}}

{{if $fields.contact}}
<div id="contact-jot-wrapper" class="form-group field" >
<p id="contact-jot-desc" >
{{$lbl_social}}
</p>

<textarea class="form-control" rows="10" cols="72" id="contact-jot-text" name="contact" >{{$contact}}</textarea>

</div>
<div id="contact-jot-end"></div>
{{/if}}


{{if $fields.interest}}
<div id="interest-jot-wrapper" class="form-group field" >
<p id="interest-jot-desc" >
{{$lbl_hobbies}}
</p>

<textarea class="form-control" rows="10" cols="72" id="interest-jot-text" name="interest" >{{$interest}}</textarea>

</div>
<div id="interest-jot-end"></div>
{{/if}}

{{if $fields.likes}}
<div id="likes-jot-wrapper" class="form-group field" >
<p id="likes-jot-desc" >
{{$lbl_likes}}
</p>

<textarea class="form-control" rows="10" cols="72" id="likes-jot-text" name="likes" >{{$likes}}</textarea>

</div>
<div id="likes-jot-end"></div>
{{/if}}

{{if $fields.dislikes}}
<div id="dislikes-jot-wrapper" class="form-group field" >
<p id="dislikes-jot-desc" >
{{$lbl_dislikes}}
</p>

<textarea class="form-control" rows="10" cols="72" id="dislikes-jot-text" name="dislikes" >{{$dislikes}}</textarea>

</div>
<div id="dislikes-jot-end"></div>
{{/if}}

{{if $fields.channels}}
<div id="channels-jot-wrapper" class="form-group field" >
<p id="channels-jot-desc" >
{{$lbl_channels}}
</p>

<textarea class="form-control" rows="10" cols="72" id="channels-jot-text" name="channels" >{{$channels}}</textarea>

</div>
<div id="channels-jot-end"></div>
{{/if}}

{{if $fields.music}}
<div id="music-jot-wrapper" class="form-group field" >
<p id="music-jot-desc" >
{{$lbl_music}}
</p>

<textarea class="form-control" rows="10" cols="72" id="music-jot-text" name="music" >{{$music}}</textarea>

</div>
<div id="music-jot-end"></div>
{{/if}}

{{if $fields.book}}
<div id="book-jot-wrapper" class="form-group field" >
<p id="book-jot-desc" >
{{$lbl_book}}
</p>

<textarea class="form-control" rows="10" cols="72" id="book-jot-text" name="book" >{{$book}}</textarea>

</div>
<div id="book-jot-end"></div>
{{/if}}

{{if $fields.tv}}
<div id="tv-jot-wrapper" class="form-group field" >
<p id="tv-jot-desc" >
{{$lbl_tv}} 
</p>

<textarea class="form-control" rows="10" cols="72" id="tv-jot-text" name="tv" >{{$tv}}</textarea>

</div>
<div id="tv-jot-end"></div>
{{/if}}

{{if $fields.film}}
<div id="film-jot-wrapper" class="form-group field" >
<p id="film-jot-desc" >
{{$lbl_film}}
</p>

<textarea class="form-control" rows="10" cols="72" id="film-jot-text" name="film" >{{$film}}</textarea>

</div>
<div id="film-jot-end"></div>
{{/if}}


{{if $fields.romance}}
<div id="romance-jot-wrapper" class="form-group field" >
<p id="romance-jot-desc" >
{{$lbl_love}}
</p>

<textarea class="form-control" rows="10" cols="72" id="romance-jot-text" name="romance" >{{$romance}}</textarea>

</div>
<div id="romance-jot-end"></div>
{{/if}}

{{if $fields.work}}
<div id="work-jot-wrapper" class="form-group field" >
<p id="work-jot-desc" >
{{$lbl_work}}
</p>

<textarea class="form-control" rows="10" cols="72" id="work-jot-text" name="work" >{{$work}}</textarea>

</div>
<div id="work-jot-end"></div>
{{/if}}

{{if $fields.education}}
<div id="education-jot-wrapper" class="form-group field" >
<p id="education-jot-desc" >
{{$lbl_school}} 
</p>

<textarea class="form-control" rows="10" cols="72" id="education-jot-text" name="education" >{{$education}}</textarea>

</div>
<div id="education-jot-end"></div>
{{/if}}

{{if $extra_fields}}
{{foreach $extra_fields as $field }}
{{include file="field_input.tpl" field=$field}}
{{/foreach}}
{{/if}}

<div class="profile-edit-submit-wrapper" >
<input type="submit" name="submit" class="profile-edit-submit-button" value="{{$submit}}" />
</div>
<div class="profile-edit-submit-end"></div>


</form>
</div>
</div>
</div>

