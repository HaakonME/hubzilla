<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$banner}}</h2>
	</div>

	<div class="clearfix section-content-wrapper">
		<form action="appman" method="post">
		{{if $guid}}
		<input type="hidden" name="guid" value="{{$guid}}" />
		{{/if}}
		{{if $author}}
		<input type="hidden" name="author" value="{{$author}}" />
		{{/if}}
		{{if $addr}}
		<input type="hidden" name="addr" value="{{$addr}}" />
		{{/if}}

		<input type="hidden" name="requires" value="{{$requires}}" />
		<input type="hidden" name="system" value="{{$system}}" />
		<input type="hidden" name="plugin" value="{{$plugin}}" />


		{{include file="field_input.tpl" field=$name}}
		{{include file="field_input.tpl" field=$categories}}
		{{include file="field_input.tpl" field=$url}}
		{{include file="field_textarea.tpl" field=$desc}}
		{{include file="field_input.tpl" field=$photo}}
		{{include file="field_input.tpl" field=$version}}
		{{include file="field_input.tpl" field=$price}}
		{{include file="field_input.tpl" field=$page}}

		{{if $embed}}
		{{include file="field_textarea.tpl" field=$embed}}
		{{/if}}

		<button class="btn btn-primary float-right" type="submit" name="submit" value="{{$submit}}">{{$submit}}</button>

		</form>
	</div>
</div>
