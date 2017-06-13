

		<a data-open="portfolioModal-{{$photo.resource_id}}" aria-controls="portfolioModal-{{$photo.resource_id}}" aria-haspopup="true" tabindex="0">
			<img class="thumbnail" src="{{$photo.src}}" alt="{{if $photo.album.name}}{{$photo.album.name}}{{elseif $photo.desc}}{{$photo.desc}}{{elseif $photo.alt}}{{$photo.alt}}{{else}}{{$photo.unknown}}{{/if}}" title="{{$photo.desc}}" id="photo-top-photo-{{$photo.resource_id}}">
		</a>

	<div class="full reveal without-overlay" id="portfolioModal-{{$photo.resource_id}}" data-reveal="f175mw-reveal" role="dialog" aria-hidden="true" data-yeti-box="portfolioModal-{{$photo.resource_id}}" data-resize="portfolioModal-{{$photo.resource_id}}">
            <h5>{{$photo.desc}}</h5>
		<img class="thumbnail" src="{{$photo.fullsrc}}" alt="{{if $photo.album.name}}{{$photo.album.name}}{{elseif $photo.desc}}{{$photo.desc}}{{elseif $photo.alt}}{{$photo.alt}}{{else}}{{$photo.unknown}}{{/if}}" title="{{$photo.desc}}" id="photo-top-photo-{{$photo.resource_id}}x">
		<button class="close-button" data-close="" aria-label="Close reveal" type="button">
			<span aria-hidden="true">×</span>
		</button>
	</div>
