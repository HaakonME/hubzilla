<form id="event-edit-form" action="{{$post}}" method="post" >

	<input type="hidden" name="event_id" value="{{$eid}}" />
	<input type="hidden" name="event_hash" value="{{$event_hash}}" />
	<input type="hidden" name="xchan" value="{{$xchan}}" />
	<input type="hidden" name="mid" value="{{$mid}}" />
	<input type="hidden" name="type" value="{{$type}}" />
	<input type="hidden" name="preview" id="event-edit-preview" value="0" />


	{{include file="field_input.tpl" field=$summary}}

	{{$s_dsel}}

	{{$f_dsel}}

	{{include file="field_checkbox.tpl" field=$nofinish}}

	<div id="advanced" style="display:none">

		{{include file="field_checkbox.tpl" field=$adjust}}

		{{if $catsenabled}}
		<div id="event-category-text"><b>{{$c_text}}</b></div>
		<div id="events-category-wrap">
			<input name="category" id="event-category" type="text" placeholder="{{$placeholdercategory}}" value="{{$category}}" class="event-cats form-control" data-role="cat-tagsinput" />
		</div>
		{{/if}}

		<div class="form-group">
			<div id="event-desc-text"><b>{{$d_text}}</b></div>

			<textarea id="comment-edit-text-desc" class="comment-edit-text-full" name="desc" >{{$d_orig}}</textarea>

			<div id="comment-tools-desc" class="comment-tools" style="display: block;" >
				<div id="comment-edit-bb-desc" class="btn-toolbar pull-left">
					<div class='btn-group'>
						<button class="btn btn-default btn-xs" title="{{$edbold}}" onclick="insertbbcomment('{{$comment}}','b', 'desc'); return false;">
							<i class="icon-bold comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$editalic}}" onclick="insertbbcomment('{{$comment}}','i', 'desc'); return false;">
							<i class="icon-italic comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$eduline}}" onclick="insertbbcomment('{{$comment}}','u', 'desc'); return false;">
							<i class="icon-underline comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$edquote}}" onclick="insertbbcomment('{{$comment}}','quote','desc'); return false;">
							<i class="icon-quote-left comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$edcode}}" onclick="insertbbcomment('{{$comment}}','code', 'desc'); return false;">
							<i class="icon-terminal comment-icon"></i>
						</button>
					</div>
					<div class='btn-group'>
						<button class="btn btn-default btn-xs" title="{{$edimg}}" onclick="insertbbcomment('{{$comment}}','img', 'desc'); return false;">
							<i class="icon-camera comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$edurl}}" onclick="insertbbcomment('{{$comment}}','url', 'desc'); return false;">
							<i class="icon-link comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$edvideo}}" onclick="insertbbcomment('{{$comment}}','video', 'desc'); return false;">
							<i class="icon-facetime-video comment-icon"></i>
						</button>
					</div>
				</div>
			</div>
		</div>
		<div class="clear"></div>
		<div class="form-group">
			<div id="event-location-text"><b>{{$l_text}}</b></div>

			<textarea id="comment-edit-text-loc" class="comment-edit-text-full" name="location">{{$l_orig}}</textarea>

			<div id="comment-tools-loc" class="comment-tools" style="display: block;" >
				<div id="comment-edit-bb-loc" class="btn-toolbar pull-left">
					<div class='btn-group'>
						<button class="btn btn-default btn-xs" title="{{$edbold}}" onclick="insertbbcomment('{{$comment}}','b', 'loc'); return false;">
							<i class="icon-bold comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$editalic}}" onclick="insertbbcomment('{{$comment}}','i', 'loc'); return false;">
							<i class="icon-italic comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$eduline}}" onclick="insertbbcomment('{{$comment}}','u', 'loc'); return false;">
							<i class="icon-underline comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$edquote}}" onclick="insertbbcomment('{{$comment}}','quote','loc'); return false;">
							<i class="icon-quote-left comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$edcode}}" onclick="insertbbcomment('{{$comment}}','code', 'loc'); return false;">
							<i class="icon-terminal comment-icon"></i>
						</button>
					</div>
					<div class='btn-group'>
						<button class="btn btn-default btn-xs" title="{{$edimg}}" onclick="insertbbcomment('{{$comment}}','img', 'loc'); return false;">
							<i class="icon-camera comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$edurl}}" onclick="insertbbcomment('{{$comment}}','url', 'loc'); return false;">
							<i class="icon-link comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$edvideo}}" onclick="insertbbcomment('{{$comment}}','video', 'loc'); return false;">
							<i class="icon-facetime-video comment-icon"></i>
						</button>
						<button class="btn btn-default btn-xs" title="{{$mapper}}" onclick="insertbbcomment('{{$comment}}','map','loc'); return false;">
							<i class="icon-globe comment-icon"></i>
						</button>
					</div>

				</div>
			</div>
		</div>
	</div>

	<div class="clear"></div>

	{{if ! $eid}}
	{{include file="field_checkbox.tpl" field=$share}}
	{{$acl}}
	{{/if}}

	<div class="clear"></div>

	<button type="button" class="btn btn-default" onclick="openClose('advanced');">{{$advanced}}</button>
	<div class="btn-group pull-right">
		<button id="event-edit-preview-btn" class="btn btn-default" type="button" title="{{$preview}}" onclick="doEventPreview(); return false;"><i class="icon-eye-open" ></i></button>
		{{if ! $eid}}
		<button id="dbtn-acl" class="btn btn-default" type="button" data-toggle="modal" data-target="#aclModal" title="{{$permissions}}"><i id="jot-perms-icon"></i></button>
		{{/if}}
		<button id="event-submit" class="btn btn-primary" type="submit" name="submit">{{$submit}}</button>
	</div>
</form>
