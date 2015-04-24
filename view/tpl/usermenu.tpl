<div id="pmenu-{{$id}}" class="pmenu{{if !$class}} widget{{else}} {{$class}}{{/if}}">
	{{if $menu.menu_desc}}
	<h3 class="pmenu-title">{{$menu.menu_desc}}{{if $edit}} <a href="mitem/{{$menu.menu_id}}" title="{{$edit}}"><i class="icon-pencil fakelink" title="{{$edit}}"></i></a>{{/if}}</h3>
	{{/if}}
	{{if $items }}
	<ul class="pmenu-body{{if !$class}} nav nav-pills nav-stacked{{/if}}">
		{{foreach $items as $mitem }}
		<li id="pmenu-item-{{$mitem.mitem_id}}" class="pmenu-item"><a href="{{$mitem.mitem_link}}" {{if $mitem.newwin}}target="_blank"{{/if}}>{{$mitem.mitem_desc}}</a></li>
		{{/foreach }}
	</ul>
	{{/if}}
	<div class="pmenu-end"></div>
</div>
