<li class="nav-item">
	<a href="mail/{{$message.mailbox}}/{{$message.id}}" class="nav-link{{if $message.selected}} active{{/if}}">
		<span class="{{if ! $message.seen || $message.selected}}font-weight-bold{{/if}}">{{$message.subject}}</span><br>
		<span class="conv-participants">{{$message.from_name}} > {{$message.to_name}}</span><br>
		<span class="wall-item-ago autotime" title="{{$message.date}}">{{$message.date}}</span>
	</a>
</li>
