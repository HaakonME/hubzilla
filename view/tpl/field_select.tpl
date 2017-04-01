	<div class="form-group">
		<label for="id_{{$field.0}}">{{$field.1}}</label>
		<select class="form-control" name="{{$field.0}}" id="id_{{$field.0}}">
			{{foreach $field.4 as $opt=>$val}}<option value="{{$opt}}" {{if $opt==$field.2}}selected="selected"{{/if}}>{{$val}}</option>{{/foreach}}
		</select>
		<small class="form-text text-muted">{{$field.3}}</small>
	</div>
