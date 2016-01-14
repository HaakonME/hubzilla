<div id="contact-entry-wrapper-{{$contact.id}}">
	<div class="section-subtitle-wrapper">
		<div class="pull-right">
			<a href="#" class="btn btn-danger btn-xs" title="{{$contact.delete_hover}}" onclick="dropItem('{{$contact.deletelink}}', '#contact-entry-wrapper-{{$contact.id}}'); return false;"><i class="icon-trash"></i> {{$contact.delete}}</a>
			<a href="{{$contact.link}}" class="btn btn-success btn-xs" title="{{$contact.edit_hover}}"><i class="icon-pencil"></i> {{$contact.edit}}</a>
		</div>
		<h3><a href="{{$contact.url}}" title="{{$contact.img_hover}}" >{{$contact.name}}</a></h3>
	</div>
	<div class="section-content-tools-wrapper">
		<div class="contact-entry-photo-wrapper" >
			<a href="{{$contact.url}}" title="{{$contact.img_hover}}" ><img class="directory-photo-img {{if $contact.classes}}{{$contact.classes}}{{/if}}" src="{{$contact.thumb}}" alt="{{$contact.name}}" /></a>
		</div>
	</div>
</div>

