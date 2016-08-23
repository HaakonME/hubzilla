<h2>{{$thing_hdr}}</h2>
<form id="thing-new-form" action="thing" method="post" class="acl-form" data-form_id="thing-new-form" data-allow_cid='{{$allow_cid}}' data-allow_gid='{{$allow_gid}}' data-deny_cid='{{$deny_cid}}' data-deny_gid='{{$deny_gid}}'>

{{if $multiprof }}
<div class="thing-profile-label">{{$profile_lbl}}</div>

<div class="thing-profile">{{$profile_select}}</div>
<div class="thing-field-end"></div>
{{/if}}


<div class="thing-verb-label">{{$verb_lbl}}</div>

<div class="thing-verb">{{$verb_select}}</div>
<div class="thing-field-end"></div>


<label class="thing-label" for="thing-term">{{$thing_lbl}}</label>
<input type="text" class="thing-input" id="thing-term" name="term" />
<div class="thing-field-end"></div>
<label class="thing-label" for="thing-url">{{$url_lbl}}</label>
<input type="text" class="thing-input" id="thing-url" name="url" />
<div class="thing-field-end"></div>
<label class="thing-label" for="thing-img">{{$img_lbl}}</label>
<input type="text" class="thing-input" id="thing-img" name="img" />
<div class="thing-field-end"></div>

{{include file="field_checkbox.tpl" field=$activity}}

<div class="thing-end"></div> 

{{if $lockstate}}
	<button id="dbtn-acl" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" onclick="return false;">
		<i id="jot-perms-icon" class="fa fa-{{$lockstate}}"></i>
	</button>
{{/if}}


<input type="submit" class="thing-submit" name="submit" value="{{$submit}}" />
</form>
{{$aclselect}}
