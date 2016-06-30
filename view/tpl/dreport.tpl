<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		{{if $table == 'item'}}
		<a href="dreport/push/{{$mid}}"><button class="btn btn-default btn-xs pull-right">{{$push}}</button></a>
		{{/if}}
		<h2>{{$title}}</h2>
	</div>

	<div>
	<table>
	{{if $entries}}
	{{foreach $entries as $e}}
	<tr>
		<td width="40%">{{$e.name}}</td>
		<td width="20%">{{$e.result}}</td>
		<td width="20%">{{$e.time}}</td>
	</tr>
	{{/foreach}}
	{{/if}}
	</table>
</div>
