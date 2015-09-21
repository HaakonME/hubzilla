<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$header}}</h2>
	</div>
	<div class="section-content-wrapper">
		{{foreach $messages as $message}}
			{{include file="mail_list.tpl"}}
		{{/foreach}}
	</div>
</div>
