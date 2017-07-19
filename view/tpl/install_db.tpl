<div class="container mt-4 mb-4">
	<div class="jumbotron">
		<h1>{{$title}}</h1>
		<hr class="my-4">
		<h2><i class="fa fa-database"></i>&nbsp; {{$pass}}</h2>
	</div>
	<div class="alert alert-info">
	{{$info_01}}<br>
	{{$info_02}}<br>
	{{$info_03}}
	</div>

	{{if $status}}
	<div class="alert alert-danger">{{$status}}</div>
	{{/if}}

	<form id="install-form" action="{{$baseurl}}/setup" method="post">
		<input type="hidden" name="phpath" value="{{$phpath}}" />
		<input type="hidden" name="pass" value="3" />

		{{include file="field_input.tpl" field=$dbhost}}
		{{include file="field_input.tpl" field=$dbport}}
		{{include file="field_input.tpl" field=$dbuser}}
		{{include file="field_password.tpl" field=$dbpass}}
		{{include file="field_input.tpl" field=$dbdata}}
		{{include file="field_select.tpl" field=$dbtype}}

		<button class="btn btn-primary" id="install-submit" type="submit" name="submit" value="{{$submit}}">{{$submit}}</button> 
	</form>
</div>

