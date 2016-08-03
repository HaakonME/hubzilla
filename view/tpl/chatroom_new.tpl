<div id="chatroom-new" class="section-content-tools-wrapper">
	<form id="chatroom-new-form" action="chat" method="post" >
		{{include file="field_input.tpl" field=$name}}
		{{include file="field_input.tpl" field=$chat_expire}}
		<div class="btn-group pull-right">
			<button id="dbtn-acl" class="btn btn-default" data-toggle="modal" data-target="#aclModal" title="{{$permissions}}" onclick="return false;" ><i id="jot-perms-icon" class="fa fa-{{$lockstate}}"></i></button>
			<button id="dbtn-submit" class="acl-submit btn btn-primary" type="submit" name="submit" value="{{$submit}}" data-formid="chatroom-new-form">{{$submit}}</button>
		</div>
		<div class="clear"></div>
	</form>
</div>
{{$acl}}
