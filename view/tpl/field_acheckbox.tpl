<tr class="highlight">
	<td>
		<label class="mainlabel" for='me_id_{{$field.0}}'>{{$field.1}}</label><br>
		<span class='field_abook_help'>{{$field.6}}</span>
	</td>
	{{if $notself}}
	<td class="abook-them">
		<input type="checkbox" name='them_{{$field.0}}' id='them_id_{{$field.0}}' value="1" disabled="disabled" {{if $field.2}}checked="checked"{{/if}} />
	</td>
	{{/if}}
	<td class="abook-me">
		<input type="checkbox" name='{{$field.0}}' class='abook-edit-me' id='me_id_{{$field.0}}' value="{{$field.4}}" {{if $field.3}}checked="checked"{{/if}}{{if $notself && $field.5}} disabled="disabled"{{/if}}/>
	</td>
	<td>
		{{if $field.5}}<span class="permission-inherited">{{$inherited}}{{if $self}}{{if $field.7}} <i class="icon-check"></i>{{else}} <i class="icon-check-empty"></i>{{/if}}{{/if}}</span>{{/if}}
	</td>
</tr>
