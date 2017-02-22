<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		{{if $authed}}
		{{if $create}}
		<a href="appman" class="pull-right btn btn-success btn-xs"><i class="fa fa-pencil-square-o"></i>&nbsp;{{$create}}</a>
		{{else}}
		<a href="apps/edit{{if $cat}}/?f=&cat={{$cat}}{{/if}}" class="pull-right btn btn-primary btn-xs">{{$manage}}</a>
		{{/if}}
		{{/if}}
		<h2>{{$title}}{{if $cat}} - {{$cat}}{{/if}}</h2>
	</div>
	<div class="section-content-wrapper">
		{{foreach $apps as $ap}}
		{{$ap}}
		{{/foreach}}
		<div class="clear"></div>
	</div>
</div>
