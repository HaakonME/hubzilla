<div id="wiki_list" class="widget">
		<h3>{{$header}}</h3>
		<ul class="nav nav-pills nav-stacked">
		{{if $wikis}}		
		{{foreach $wikis as $wiki}}
        <li>{{if $owner}}<a href="#" onclick="wiki_show_edit_wiki_form('{{$wiki.title}}', '{{$wiki.resource_id}}'); return false;" class="pull-right wikilist" title="{{$edit}}"><i class="fa fa-pencil"></i></a>{{/if}}
			<a href="#" onclick="wiki_download_wiki('{{$wiki.resource_id}}'); return false;" title="{{$download}}" class="pull-right wikilist"><i class="fa fa-download"></i></a>
			<a href="/wiki/{{$channel}}/{{$wiki.urlName}}/Home" title="{{$view}}">{{$wiki.title}}</a>
        </li> 
		{{/foreach}}
		{{/if}}
		{{if $owner}}<li><a href="#" class="fakelink" onclick="wiki_show_new_wiki_form(); return false;"><i id="new-wiki-button" class="fa fa-plus-circle"></i>&nbsp;{{$addnew}}</a></li>{{/if}}
		</ul>
</div>

