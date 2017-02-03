{{foreach $apps as $app}}
<li><a href="{{$app.url}}">{{if $app.icon}}<i class="generic-icons fa fa-fw fa-{{$app.icon}}"></i>{{else}}<img src="{{$app.photo}}" width="16" height="16" style="margin-right:9px;"/>{{/if}}{{$app.name}}</a></li>
{{/foreach}}
{{if $localuser}}
<li class="divider"></li>
<li><a href="/apps/edit"><i class="generic-icons fa fa-fw fa-plus-circle"></i>Add Apps</a></li>
{{/if}}

