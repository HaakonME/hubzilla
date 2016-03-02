<div class="generic-content-wrapper">
<h2>{{$title}}</h2>

<form action="admin/profs" method="post">

{{include file="field_textarea.tpl" field=$basic}}
{{include file="field_textarea.tpl" field=$advanced}}

<input type="submit" name="submit" value="{{$submit}}" />

</form>

<div>{{$all_desc}}</div>

<div>{{$all}}</div>

<br /><br />
<div>{{$cust_field_desc}}</div>

<button href="admin/profs/new">{{$new}}</button>

{{if $cust_fields}}
<table>
{{foreach $cust_fields as $field}}
<tr><td>{{$field.field_desc}}</td><td>{{$field.field_name}}</td><td><a href="admin/profs/{{$field.id}}" >{{$edit}}</a> <a href="admin/profs/drop/{{$field.id}}">{{$drop}}</a></td></tr>
{{/foreach}}
</table>
{{/if}}


</div>
