<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$title}}</h2>
		<div class="clear"></div>
	</div>
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
	<ul>
	{{foreach $tokens as $t}}
	<li><a href="settings/tokens/{{$t.atoken_id}}">{{$t.atoken_name}}</a> <a href="settings/tokens/{{$t.atoken_id}}/drop"><i class="fa fa-remove btn btn-xs btn-default pull-right"></i></a></li>
	{{/foreach}}
	</ul>
	</div>
	{{/if}}

</div>
