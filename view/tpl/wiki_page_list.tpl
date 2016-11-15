{{if $not_refresh}}<div id="wiki_page_list_container" {{if $hide}} style="display: none;" {{/if}}>{{/if}}
<div id="wiki_page_list" class="widget" >
	<h3>{{$header}}
			<i id="new-page-button" class="pull-right generic-icons fakelink fa fa-plus" title="New page" onclick="wiki_show_new_page_form();"></i>
	</h3>
	<ul class="nav nav-pills nav-stacked">
		{{foreach $pages as $page}}
		<li><a href="/wiki/{{$channel}}/{{$wikiname}}/{{$page.url}}">{{$page.title}}</a></li>
		{{/foreach}}
	</ul>
</div>
{{if $not_refresh}}</div>{{/if}}
