<div id="wiki_list" class="widget">
	<h3>{{$header}}</h3>
	<ul class="nav nav-pills nav-stacked">
		{{foreach $wikis as $wiki}}
		<div style="padding-bottom: 10px;">
        <li class="dropdown" id="wiki-{{$wiki.resource_id}}">
						<a class="btn btn-md fa fa-caret-down dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></a>
						<a href="/wiki/{{$channel}}/{{$wiki.urlName}}/Home" title="View  {{$wiki.title}}"><b>{{$wiki.title}}</b></a>
            <ul class="dropdown-menu pull-left">  
							<li><a href="#" onclick="wiki_download_wiki('{{$wiki.resource_id}}'); return false;" title="Download  {{$wiki.title}}"><i class="fa fa-download"></i><span style="padding-left: 10px;">Download</span></a></li>
              {{if $showControls}}
              <li class="divider"></li>  
              <li><a href="#" onclick="wiki_delete_wiki('{{$wiki.title}}','{{$wiki.resource_id}}'); return false;" title="Delete {{$wiki.title}}"><i class="fa fa-trash-o"></i><span style="padding-left: 10px;">Delete wiki</span></a></li>                                
              {{/if}}
            </ul>  
        </li> 
				</div>
		{{/foreach}}
	</ul>
</div>

