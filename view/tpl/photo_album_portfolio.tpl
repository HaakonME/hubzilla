{{if isset($mode) && $mode == 'orbit'}}
<div class="row">
		<div class="orbit small-12 medium-9 large-9 columns" id="photo-album-contents-{{$album_id}}" role="region" aria-label="portfolioOrbit-{{$album_id}}" data-orbit data-options="animInFromLeft:fade-in; animInFromRight:fade-in; animOutToLeft:fade-out; animOutToRight:fade-out;">
               
		  <ul class="orbit-container">
 <button class="orbit-previous"><span class="show-for-sr">Previous Slide</span>&#9664;&#xFE0E;</button>
		  <button class="orbit-next"><span class="show-for-sr">Next Slide</span>&#9654;&#xFE0E;</button>
			{{foreach $photos as $photo}}
			{{include file="photo_portfolio_orbit.tpl"}}
			{{/foreach}}
                    </ul>
                    <nav class="orbit-bullets">
                    <button class="is-active" data-slide="0"><span class="show-for-sr">First slide details.</span><span class="show-for-sr">Current Slide</span></button>
                    <button data-slide="1"><span class="show-for-sr">Second slide details.</span></button>
                    <button data-slide="2"><span class="show-for-sr">Third slide details.</span></button>
                    <button data-slide="3"><span class="show-for-sr">Fourth slide details.</span></button>
                    </nav>
			<div id="page-end"></div>
		</div>
<div class="photos-end"></div>
<script>$(document).ready(function() { loadingPage = false; justifyPhotos('photo-album-contents-{{$album_id}}'); });</script>
<div id="page-spinner"></div>
</div>
{{elseif isset($mode) && $mode =='card'}}
<div class="row">
		<div class="small-12 medium-9 large-9 columns" id="photo-album-contents-{{$album_id}}">
			{{foreach $photos as $photo}}
			{{include file="photo_portfolio_card.tpl"}}
			{{/foreach}}
			<div id="page-end"></div>
		</div>
<div class="photos-end"></div>
<script>$(document).ready(function() { loadingPage = false; justifyPhotos('photo-album-contents-{{$album_id}}'); });</script>
<div id="page-spinner"></div>
</div>
{{else}}
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
{{/if}}

