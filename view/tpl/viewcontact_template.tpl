<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$title}}</h2>
	</div>
	<div class="section-content-wrapper clearfix">
		{{foreach $contacts as $contact}}
		{{include file="contact_template.tpl"}}
		{{/foreach}}
		<div id="page-end"></div>
	</div>
</div>
<script>$(document).ready(function() { loadingPage = false;});</script>
<div id="page-spinner" class="spinner-wrapper">
	<div class="spinner m"></div>
</div>
