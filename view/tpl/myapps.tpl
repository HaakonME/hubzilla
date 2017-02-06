<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$title}}{{$cat}}</h2>
	</div>
	<div class="section-content-wrapper">
		{{foreach $apps as $ap}}
		{{$ap}}
		{{/foreach}}
		<div class="clear"></div>
	</div>
</div>
