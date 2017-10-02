<div class="vcard h-card">
	{{if ! $zcard}}
	<div id="profile-photo-wrapper"><img class="photo u-photo" src="{{$profile.photo}}?rev={{$profile.picdate}}" alt="{{$profile.fullname}}"></div>
	{{/if}}
	{{if $connect}}
	<div class="connect-btn-wrapper"><a href="{{$connect_url}}" class="btn btn-block btn-success btn-sm"><i class="fa fa-plus"></i> {{$connect}}</a></div>
	{{/if}}
	{{if ! $zcard}}
	{{if $editmenu.multi}}
	<div class="dropdown float-right">
		<a class="dropdown-toggle" data-toggle="dropdown" href="#" ><i class="fa fa-pencil" title="{{$editmenu.edit.1}}"></i></a>
		<div class="dropdown-menu dropdown-menu-right" role="menu">
			{{foreach $editmenu.menu.entries as $e}}
			<a href="profiles/{{$e.id}}" class="dropdown-item"><img class="menu-img-1" src='{{$e.photo}}'>{{$e.profile_name}}</a>
			{{/foreach}}
			<a href="profile_photo" class="dropdown-item">{{$editmenu.menu.chg_photo}}</a>
			{{if $editmenu.menu.cr_new}}<a href="profiles/new" id="profile-listing-new-link" class="dropdown-item">{{$editmenu.menu.cr_new}}</a>{{/if}}
		</div>
	</div>
	{{elseif $editmenu}}
	<a class="profile-edit-side-link" href="{{$editmenu.edit.0}}" ><i class="fa fa-pencil" title="{{$editmenu.edit.1}}"></i></a>
	{{/if}}
	{{/if}}

	{{if ! $zcard}}
	<div class="fn p-name">{{$profile.fullname}}{{if $profile.online}} <i class="fa fa-asterisk online-now" title="{{$profile.online}}"></i>{{/if}}</div>
	{{if $reddress}}<div class="reddress">{{$profile.reddress}}</div>{{/if}}		
	{{/if}}
	{{if $pdesc}}<div class="title">{{$profile.pdesc}}</div>{{/if}}
	{{if $location}}
		<dl class="location"><dt class="location-label">{{$location}}</dt> 
		<dd class="adr h-adr">
			{{if $profile.address}}<div class="street-address p-street-address">{{$profile.address}}</div>{{/if}}
			<span class="city-state-zip">
				<span class="locality p-locality">{{$profile.locality}}</span>{{if $profile.locality}}, {{/if}}
				<span class="region p-region">{{$profile.region}}</span>
				<span class="postal-code p-postal-code">{{$profile.postal_code}}</span>
			</span>
			{{if $profile.country_name}}<span class="country-name p-country-name">{{$profile.country_name}}</span>{{/if}}
		</dd>
		</dl>
	{{/if}}

	{{if $gender}}<dl class="mf"><dt class="gender-label">{{$gender}}</dt> <dd class="p-gender">{{if $profile.gender_icon}}<i class="fa fa-{{$profile.gender_icon}}"></i>&nbsp;{{/if}}{{$profile.gender}}</dd></dl>{{/if}}

	{{if $marital}}<dl class="marital"><dt class="marital-label"><span class="heart"><i class="fa fa-heart"></i>&nbsp;</span>{{$marital}}</dt><dd class="marital-text">{{$profile.marital}}</dd></dl>{{/if}}

	{{if $homepage}}<dl class="homepage"><dt class="homepage-label">{{$homepage}}</dt><dd class="homepage-url u-url">{{$profile.homepage}}</dd></dl>{{/if}}

<div class="hcard-addon"></div>
</div>
<div class="clear"></div>

{{$rating}}

{{$chanmenu}}

{{$contact_block}}


