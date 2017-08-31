<div id="threads-begin"></div>
{{if $photo_item}}
{{$photo_item}}
{{/if}}
{{foreach $threads as $thread_item}}
{{include file="{{$thread_item.template}}" item=$thread_item}}
{{/foreach}}
<div id="threads-end"></div>
<div id="conversation-end"></div>
