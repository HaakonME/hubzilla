<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<a class="btn btn-xs btn-danger pull-right" href="mail/{{$mailbox}}/dropconv/{{$thread_id}}" onclick="return confirmDelete();"><i class="icon-trash"></i> {{$delete}}</a>
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
