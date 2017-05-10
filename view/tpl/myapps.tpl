<div class="generic-content-wrapper">
	<div class="section-title-wrapper clearfix">
		{{if $authed}}
		{{if $create}}
		<a href="appman" class="pull-right btn btn-success btn-sm"><i class="fa fa-pencil-square-o"></i>&nbsp;{{$create}}</a>
		{{else}}
		<a href="apps/edit{{if $cat}}/?f=&cat={{$cat}}{{/if}}" class="pull-right btn btn-primary btn-sm">{{$manage}}</a>
		{{/if}}
		{{/if}}
		<h2>{{$title}}{{if $cat}} - {{$cat}}{{/if}}</h2>
	</div>
	<div class="clearfix section-content-wrapper">
		{{foreach $apps as $ap}}
		{{$ap}}
		{{/foreach}}
	</div>
</div>
