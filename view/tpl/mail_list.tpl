<a href="{{$message.from_url}}" class ="mail-list" ><img class="mail-list-sender-photo" src="{{$message.from_photo}}" alt="{{$message.from_name}}" /></a>
<span class="mail-list">{{$message.from_name}}</span>
<span class="mail-list {{if $message.seen}}seen{{else}}unseen{{/if}}"><a href="mail/{{$message.id}}" class="mail-link">{{$message.subject}}</a></span>
<span class="mail-list" title="{{$message.date}}">{{$message.date}}</span>
<span class="mail-list mail-list-remove" class="btn btn-default btn-sm"><a href="message/dropconv/{{$message.id}}" onclick="return confirmDelete();"  title="{{$message.delete}}"  class="btn btn-default btn-sm" ><i class="icon-trash mail-icons drop-icons"></i></a></span>
<div class="clear"></div>
