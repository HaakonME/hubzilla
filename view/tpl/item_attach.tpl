{{if $attaches}}
{{foreach $attaches as $a}}
<a class="dropdwn-item" href="{{$a.url}}" title="{{$a.title}}"><i class="fa {{$a.icon}} attach-icons"></i>&nbsp;{{$a.label}}</a>
{{/foreach}}
{{/if}}
