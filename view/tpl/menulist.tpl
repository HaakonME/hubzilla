<div class="generic-content-wrapper-styled">
<h1>{{$title}}</h1>
<a href="menu/new{{if $sys}}?f=&sys=1{{/if}}" title="{{$hintnew}}">{{$hintnew}}</a>
<br>

{{if $menus }}
<ul id="menulist">
{{foreach $menus as $m }}
<li>
<a href="menu/{{$m.menu_id}}{{if $sys}}?f=&sys=1{{/if}}" title="{{$hintedit}}"><i class="icon-pencil design-icons design-edit-icon btn btn-default"></i></a>
<a href="menu/{{$m.menu_id}}/drop{{if $sys}}?f=&sys=1{{/if}}" title="{{$hintdrop}}"><i class="icon-trash drop-icons design-icons design-remove-icon btn btn-default"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;{{if $m.bookmark}}<i class="icon-bookmark" title="{{$bmark}}" ></i>&nbsp;{{/if}}<a href="mitem/{{$m.menu_id}}/new{{if $sys}}?f=&sys=1{{/if}}" title="{{$hintcontent}}">{{$m.menu_name}}</a>
</li>
{{/foreach}}
</ul>
{{/if}}
</div>