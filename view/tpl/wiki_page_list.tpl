{{if $not_refresh}}<div id="wiki_page_list_container" {{if $hide}} style="display: none;" {{/if}}>{{/if}}
<div id="wiki_page_list" class="widget" >
	<h3>{{$header}}</h3>

	<ul class="nav nav-pills nav-stacked">
		{{if $pages}}
		{{foreach $pages as $page}}
		<li><a href="/wiki/{{$channel}}/{{$wikiname}}/{{$page.url}}">{{$page.title}}</a></li>
		{{/foreach}}
		{{/if}}
		{{if $canadd}}<li><a href="#" onclick="wiki_show_new_page_form(); return false;"><i class="fa fa-plus-circle"></i>&nbsp;{{$addnew}}</a></li>{{/if}}
	</ul>
</div>
{{if $not_refresh}}</div>{{/if}}
