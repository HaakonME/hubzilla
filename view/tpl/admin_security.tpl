<div class="generic-content-wrapper-styled" id='adminpage'>
	<h1>{{$title}} - {{$page}}</h1>

	<form action="{{$baseurl}}/admin/security" method="post">

	<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>


	{{include file="field_checkbox.tpl" field=$block_public}}


	{{include file="field_textarea.tpl" field=$whitelisted_sites}}
	{{include file="field_textarea.tpl" field=$blacklisted_sites}}

	{{include file="field_textarea.tpl" field=$whitelisted_channels}}
	{{include file="field_textarea.tpl" field=$blacklisted_channels}}

	{{if $embedhelp1}}
	<div class="section-content-info-wrapper">{{$embedhelp1}}</div>
	{{/if}}

	<div style="margin-left: 15px; margin-bottom: 10px;">
	<div class="descriptive-text">{{$embedhelp2}}</div>
	<div style="margin-left: 15px;">
	<div class="descriptive-text">{{$embedhelp3}}</div>
	</div>
	<div class="descriptive-text">{{$embedhelp4}}</div>
	</div>

	{{include file="field_textarea.tpl" field=$embed_allow}}
	{{include file="field_textarea.tpl" field=$embed_deny}}


	<div class="admin-submit-wrapper" >
		<input type="submit" name="submit" class="admin-submit" value="{{$submit}}" />
	</div>

	</form>

</div>
