<div class="container mt-4 mb-4">
	<div class="jumbotron">
		<h1>{{$title}}</h1>
		<hr class="my-4">
		<h2><i class="fa fa-{{$icon}}"></i>&nbsp; {{$pass}}</h2>
	</div>

	{{if $status}}
	<div class="alert alert-danger">{{$status}}</div>
	{{/if}}

	<div class="alert alert-info">{{$text}}</div>
	<br>
	{{$what_next}}
</div>
