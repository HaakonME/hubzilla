
    <li class="orbit-slide">
      <img class="orbit-image" src="{{$photo.src}}" alt="{{if $photo.album.name}}{{$photo.album.name}}{{elseif $photo.desc}}{{$photo.desc}}{{elseif $photo.alt}}{{$photo.alt}}{{else}}{{$photo.unknown}}{{/if}}" title="{{$photo.desc}}" id="photo-top-photo-{{$photo.resource_id}}o">
      <figcaption class="orbit-caption">{{$photo.desc}}</figcaption>
    </li>
