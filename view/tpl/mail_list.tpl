<li>
	<a href="mail/{{$message.id}}" class="mail-link">
		<span class="{{if $message.seen}}seen{{else}}unseen{{/if}}">{{$message.subject}}</span><br>
		<span class="conv-participants">{{$message.from_name}} > {{$message.to_name}}</span><br>
		<span class="wall-item-ago autotime" title="{{$message.date}}">{{$message.date}}</span>
	</a>
</li>
