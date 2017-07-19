<div class="container mt-4 mb-4">
	<div class="jumbotron">
		<h1>{{$title}}</h1>
		<hr class="my-4">
		<h2><i class="fa fa-cogs"></i>&nbsp; {{$pass}}</h2>
	</div>

	{{if $status}}
	<div class="alert alert-danger">{{$status}}</div>
	{{/if}}

	<form id="install-form" action="{{$baseurl}}/setup" method="post">
		<input type="hidden" name="phpath" value="{{$phpath}}" />
		<input type="hidden" name="dbhost" value="{{$dbhost}}" />
		<input type="hidden" name="dbport" value="{{$dbport}}" />
		<input type="hidden" name="dbuser" value="{{$dbuser}}" />
		<input type="hidden" name="dbpass" value="{{$dbpass}}" />
		<input type="hidden" name="dbdata" value="{{$dbdata}}" />
		<input type="hidden" name="dbtype" value="{{$dbtype}}" />
		<input type="hidden" name="pass" value="4" />

		{{include file="field_input.tpl" field=$adminmail}}
		{{include file="field_input.tpl" field=$siteurl}}
		{{include file="field_select_grouped.tpl" field=$timezone}}

		<button class="btn btn-primary" id="install-submit" type="submit" name="submit" value="{{$submit}}">{{$submit}}</button>
	</form>
</div>
