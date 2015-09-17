<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$prvmsg_header}}</h2>
	</div>
	<div class="section-content-wrapper">
		{{foreach $mails as $mail}}
			{{include file="mail_conv.tpl"}}
		{{/foreach}}

		{{if $canreply}}
		{{include file="prv_message.tpl"}}
		{{else}}
		{{$unknown_text}}
		{{/if}}
	</div>
</div>
