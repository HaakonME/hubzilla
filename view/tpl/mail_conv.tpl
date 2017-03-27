<div id="mail-{{$mail.id}}" class="mb-2 clearfix mail-conv-outside-wrapper">
	<div class="mb-2 clearfix wall-item-head">
		<div class="wall-item-info" >
			<a href="{{$mail.from_url}}"><img class="wall-item-photo" src="{{$mail.from_photo}}" alt="{{$mail.from_name}}" /></a>
		</div>
		<div class="mail-conv-detail">
			{{if $mail.is_recalled}}<strong>{{$mail.is_recalled}}</strong>{{/if}}
			<div class="wall-item-name"><a class="wall-item-name-link" href="{{$mail.from_url}}">{{$mail.from_name}}</a></div>
			<div class="autotime wall-item-ago" title="{{$mail.date}}">{{$mail.date}}</div>
		</div>
	</div>
	<div class="clearfix mail-conv-content">
		<div class="clearfix mail-conv-body">
			{{$mail.body}}
		</div>
		{{if $mail.attachments}}
		<div class="dropdown float-left">
			<button type="button" class="btn btn-outline-secondary btn-sm wall-item-like dropdown-toggle" data-toggle="dropdown" id="attachment-menu-{{$item.id}}"><i class="fa fa-fw fa-paperclip"></i></button>
			<div class="dropdown-menu" role="menu" aria-labelledby="attachment-menu-{{$item.id}}">{{$mail.attachments}}</div>
		</div>
		{{/if}}
		<div class="float-right dropdown">
			<button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown" id="mail-item-menu-{{$mail.id}}">
				<i class="fa fa-cog"></i>
			</button>
			<div class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="mail-item-menu-{{$mail.id}}">
				{{if $mail.can_recall}}
				<a class="dropdown-item" href="mail/{{$mail.mailbox}}/recall/{{$mail.id}}" title="{{$mail.recall}}" id="mail-conv-recall-icon-{{$mail.id}}"><i class="fa fa-fw fa-undo"></i>&nbsp;{{$mail.recall}}</a>
				{{/if}}
				<a class="dropdown-item" href="#" onclick="dropItem('mail/{{$mail.mailbox}}/drop/{{$mail.id}}', '#mail-{{$mail.id}}'); return false;" title="{{$mail.delete}}" id="mail-conv-delete-icon-{{$mail.id}}"><i class="fa fa-fw fa-trash-o"></i>&nbsp;{{$mail.delete}}</a>
				{{if $mail.can_recall}}
				<div class="dropdown-divider"></div>
				<a class="dropdown-item" href="dreport/mail/{{$mail.mid}}" title="{{$mail.dreport}}" id="mail-conv-dreport-icon-{{$mail.id}}">{{$mail.dreport}}</a>
				{{/if}}
			</div>
		</div>
	</div>
</div>
