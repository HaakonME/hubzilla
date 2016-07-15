<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$title}}</h2>
		<div class="clear"></div>
	</div>
	<div class="atoken-text descriptive-text">{{$desc}}</div>
	<form action="settings/tokens" id="settings-account-form" method="post" autocomplete="off" >
		<input type='hidden' name='form_security_token' value='{{$form_security_token}}'>
		{{if $atoken}}<input type="hidden" name="atoken_id" value="{{$atoken.atoken_id}}" />{{/if}}
		<div class="section-content-tools-wrapper">
			{{include file="field_input.tpl" field=$name}}
			{{include file="field_input.tpl" field=$token}}
			{{include file="field_input.tpl" field=$expires}}
			<div class="settings-submit-wrapper" >
				<button type="submit" name="submit" class="btn btn-primary">{{$submit}}</button>
			</div>
		</div>
	</form>
	{{if $tokens}}
	<div>
	<ul class="atoken-list">
	{{foreach $tokens as $t}}
	<li><span class="pull-right atoken-drop"><a href="settings/tokens/{{$t.atoken_id}}/drop"><i class="fa fa-trash btn btn-xs btn-default"></i></a></span><a href="settings/tokens/{{$t.atoken_id}}">{{$t.atoken_name}}</a></li>
	{{/foreach}}
	</ul>
	</div>
	{{/if}}

</div>
