<div class="card" style="width: 300px;box-shadow: 0px 10px 6px -6px rgba(119,119,119,0.6);border-style: solid;border-width: 1px;border=color: rgba(230,230,230,0.6);margin:10px 5px 10px 5px;">
  <img src="{{$photo.src}}" alt="{{if $photo.album.name}}{{$photo.album.name}}{{elseif $photo.desc}}{{$photo.desc}}{{elseif $photo.alt}}{{$photo.alt}}{{else}}{{$photo.unknown}}{{/if}}" title="{{$photo.desc}}" id="photo-top-photo-{{$photo.resource_id}}">
  <div class="card-divider">
    <h4>{{$photo.desc}}</h4>
  </div>
</div>
