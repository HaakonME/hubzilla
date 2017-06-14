<div class="row column">
		<div id="photo-album-contents-{{$album_id}}">
			{{foreach $photos as $photo}}
			{{include file="photo_portfolio.tpl"}}
			{{/foreach}}
			<div id="page-end"></div>
		</div>
<div class="photos-end"></div>
<script>$(document).ready(function() { loadingPage = false; justifyPhotos('photo-album-contents-{{$album_id}}'); });</script>
<div id="page-spinner"></div>
</div>