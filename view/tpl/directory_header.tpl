<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$dirlbl}}{{if $search}}:&nbsp;{{$safetxt}}{{/if}}</h2>
	</div>
	{{foreach $entries as $entry}}
		{{include file="direntry.tpl"}}
	{{/foreach}}
	<div id="page-end"></div>
	<div class="clear"></div>
</div>
<script>$(document).ready(function() { loadingPage = false;});</script>
<div id="page-spinner"></div>
