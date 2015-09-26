<div class="mail-conv-outside-wrapper">
	<div class="mail-conv-sender" >
		<a href="{{$mail.from_url}}"><img class="mail-conv-sender-photo" src="{{$mail.from_photo}}" alt="{{$mail.from_name}}" /></a>
	</div>
	<div class="mail-conv-detail">
		{{if $mail.is_recalled}}<strong>{{$mail.is_recalled}}</strong>{{/if}}
		<div class="mail-conv-sender-name" ><a href="{{$mail.from_url}}">{{$mail.from_name}}</a></div>
		<div class="mail-conv-date autotime wall-item-ago" title="{{$mail.date}}">{{$mail.date}}</div>
		<div class="mail-conv-body">{{$mail.body}}</div>
		<div class="btn-group pull-right" id="mail-conv-delete-wrapper-{{$mail.id}}" >
			{{if $mail.can_recall}}
			<a href="mail/recall/{{$mail.id}}" title="{{$mail.recall}}" id="mail-conv-recall-icon-{{$mail.id}}" class="btn btn-default" ><i class="icon-undo mail-icons"></i></a>
			{{/if}}
			<a href="mail/drop/{{$mail.id}}" onclick="return confirmDelete();" title="{{$mail.delete}}" id="mail-conv-delete-icon-{{$mail.id}}" class="btn btn-default" ><i class="icon-trash mail-icons"></i></a>
		</div>
	</div>
	<div class="clear"></div>
</div>
