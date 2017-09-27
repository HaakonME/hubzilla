<div class="generic-content-wrapper">
	<div class="section-title-wrapper clearfix">
		<div class="btn-group pull-right">
			<button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{$sort}}">
				<i class="fa fa-sort"></i>
			</button>
			<div class="dropdown-menu dropdown-menu-right">
				<a class="dropdown-item" href="directory?f=&order=date{{$suggest}}">{{$date}}</a>
				<a class="dropdown-item" href="directory?f=&order=normal{{$suggest}}">{{$normal}}</a>
				<a class="dropdown-item" href="directory?f=&order=reversedate{{$suggest}}">{{$reversedate}}</a>
				<a class="dropdown-item" href="directory?f=&order=reverse{{$suggest}}">{{$reverse}}</a>
			</div>
		</div>
		<h2>{{$dirlbl}}{{if $search}}:&nbsp;{{$safetxt}}{{/if}}</h2>
	</div>
	{{foreach $entries as $entry}}
		{{include file="direntry.tpl"}}
	{{/foreach}}
	<div id="page-end"></div>
</div>
<script>$(document).ready(function() { loadingPage = false;});</script>
<div id="page-spinner" class="spinner-wrapper">
	<div class="spinner m"></div>
</div>
