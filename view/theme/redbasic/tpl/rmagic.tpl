<div class="generic-content-wrapper-styled">
	<h3>{{$title}}</h3>
	<form action="rmagic" method="post" >
		<div class="form-group">
			{{include file="field_input.tpl" field=$address}}
			<input class="btn btn-primary" type="submit" name="submit" id="rmagic-submit-button" value="{{$submit}}" />
		</div>
	</form>
</div>
