<div class="{{if !$no_fullscreen_btn}}generic-content-wrapper{{/if}}">
	<div class="section-title-wrapper">
		<div class="pull-right">
			{{if $order}}
			<a class="btn btn-outline-secondary btn-sm" href="{{$order.1}}" title="{{$order.0}}"><i class="fa fa-sort"></i></a>
			{{/if}}
			<div class="btn-group btn-group">
				{{if $album_edit.1}}
				<i class="fa fa-pencil btn btn-outline-secondary btn-sm" title="{{$album_edit.0}}" onclick="openClose('photo-album-edit-wrapper'); closeMenu('photo-upload-form');"></i>
				{{/if}}
				{{if $can_post}}
				<button class="btn btn-sm btn-success btn-sm" title="{{$usage}}" onclick="openClose('photo-upload-form'); closeMenu('photo-album-edit-wrapper');"><i class="fa fa-arrow-circle-o-up"></i>&nbsp;{{$upload.0}}</button>
				{{/if}}
			</div>
		</div>
		<h2>{{$album}}</h2>
		<div class="clear"></div>
	</div>
	{{$upload_form}}
	{{$album_edit.1}}
	<div class="section-content-wrapper-np">
		<div id="photo-album-contents-{{$album_id}}" style="display: none">
			{{foreach $photos as $photo}}
				{{include file="photo_top.tpl"}}
			{{/foreach}}
			<div id="page-end"></div>
		</div>
	</div>
</div>
<div class="photos-end"></div>
<div id="page-spinner" class="spinner-wrapper">
	<div class="spinner m"></div>
</div>
<script>
$(document).ready(function() {
	loadingPage = false;
	justifyPhotos('photo-album-contents-{{$album_id}}');
});
</script>
