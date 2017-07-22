<div class="section-subtitle-wrapper">
	<div class="pull-right">
		{{if $channel.default_links}}
		{{if $channel.default}}
		<div>
			<i class="fa fa-check-square-o"></i>&nbsp;{{$msg_default}}
		</div>
		{{else}}
		<a href="manage/{{$channel.channel_id}}/default" class="make-default-link">
			<i class="fa fa-square-o"></i>&nbsp;{{$msg_make_default}}
		</a>
		{{/if}}
		{{/if}}
		{{if $channel.delegate}}
			{{$delegated_desc}}
		{{/if}}
	</div>
	<h3>
		{{if $selected == $channel.channel_id}}
		<i class="fa fa-circle text-success" title="{{$msg_selected}}"></i>
		{{/if}}
		{{if $channel.delegate}}
		<i class="fa fa-arrow-circle-right" title="{{$delegated_desc}}"></i>
		{{/if}}
		{{if $selected != $channel.channel_id}}<a href="{{$channel.link}}" title="{{$channel.channel_name}}">{{/if}}
			{{$channel.channel_name}}
		{{if $selected != $channel.channel_id}}</a>{{/if}}
	</h3>
	<div class="clear"></div>
</div>
<div class="section-content-wrapper">
	<div class="channel-photo-wrapper">
		{{if $selected != $channel.channel_id}}<a href="{{$channel.link}}" class="channel-selection-photo-link" title="{{$channel.channel_name}}">{{/if}}
			<img class="channel-photo" src="{{$channel.xchan_photo_m}}" alt="{{$channel.channel_name}}" />
		{{if $selected != $channel.channel_id}}</a>{{/if}}
	</div>
	<div class="channel-notifications-wrapper">
		{{if !$channel.delegate}}
		<div class="channel-notification">
			<i class="fa fa-fw fa-envelope{{if $channel.mail != 0}} text-danger{{/if}}"></i>
			{{if $channel.mail != 0}}<a href="manage/{{$channel.channel_id}}/mail/combined">{{/if}}{{$channel.mail|string_format:$mail_format}}{{if $channel.mail != 0}}</a>{{/if}}
		</div>
		<div class="channel-notification">
			<i class="fa fa-fw fa-user{{if $channel.intros != 0}} text-danger{{/if}}"></i>
			{{if $channel.intros != 0}}<a href='manage/{{$channel.channel_id}}/connections/ifpending'>{{/if}}{{$channel.intros|string_format:$intros_format}}{{if $channel.intros != 0}}</a>{{/if}}
		</div>
		{{/if}}
	</div>
</div>
