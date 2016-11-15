<div id="wiki_list" class="widget">
		
		<h3>{{$header}}
				<i id="new-wiki-button" class="pull-right generic-icons fakelink fa fa-plus" title="New wiki" onclick="wiki_show_new_wiki_form();"></i>
		</h3>
				
	<div>
		{{foreach $wikis as $wiki}}
        <div class="form-group" id="wiki-{{$wiki.resource_id}}">
						<a href="/wiki/{{$channel}}/{{$wiki.urlName}}/Home" title="View  {{$wiki.title}}"><b>{{$wiki.title}}</b></a>
						<i id="edit-wiki-button" class="pull-right generic-icons fakelink fa fa-edit" onclick="wiki_show_edit_wiki_form('{{$wiki.title}}', '{{$wiki.resource_id}}');" title="Edit {{$wiki.title}}"></i>
						<i class="pull-right generic-icons fakelink fa fa-download" onclick="wiki_download_wiki('{{$wiki.resource_id}}'); return false;" title="Download  {{$wiki.title}}"></i>
        </div> 
		{{/foreach}}
	</div>
</div>

