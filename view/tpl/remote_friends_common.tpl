<div class="widget">
	<h3>{{$desc}}</h3>
	{{if $linkmore}}
	<a class="allcontact-link" href="{{$base}}/common/{{$uid}}">{{$more}}</a>
	{{/if}}
	{{if $items}}
	<div class="contact-block-content">
		{{foreach $items as $item}}
		<div class="contact-block-div">
			<a class="contact-block-link mpfriend" href="{{$base}}/chanview?f=&url={{$item.xchan_url}}"><img class="contact-block-img mpfriend" src="{{$item.xchan_photo_s}}"alt="{{$item.xchan_name}}" title="{{$item.xchan_name}} [{{$item.xchan_addr}}]" /></a>
		</div>
		{{/foreach}}
	</div>
	{{/if}}
<div>

