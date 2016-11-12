<div id="wiki_list" class="widget">
	<h3>{{$header}}</h3>
	<div>
		{{foreach $wikis as $wiki}}
        <div class="form-group" id="wiki-{{$wiki.resource_id}}">
						<a href="/wiki/{{$channel}}/{{$wiki.urlName}}/Home" title="View  {{$wiki.title}}"><b>{{$wiki.title}}</b></a>
						<i class="pull-right generic-icons fakelink fa fa-trash-o" onclick="wiki_delete_wiki('{{$wiki.title}}','{{$wiki.resource_id}}'); return false;" title="Delete {{$wiki.title}}"></i>
						<i class="pull-right generic-icons fakelink fa fa-download" onclick="wiki_download_wiki('{{$wiki.resource_id}}'); return false;" title="Download  {{$wiki.title}}"></i>
        </div> 
		{{/foreach}}
	</div>
</div>

