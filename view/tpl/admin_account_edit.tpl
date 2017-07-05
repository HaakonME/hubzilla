<h2>{{$title}}</h2>

<h3>{{$account.account_email}}</h3>


<form action="admin/account_edit/{{$account.account_id}}" method="post" >
<input type="hidden" name="aid" value="{{$account.account_id}}" />

{{include file="field_password.tpl" field=$pass1}}
{{include file="field_password.tpl" field=$pass2}}
{{include file="field_select.tpl" field=$account_level}}
{{include file="field_select.tpl" field=$account_language}}
{{include file="field_input.tpl" field=$service_class}}


<input type="submit" name="submit" value="{{$submit}}" />

</form>
