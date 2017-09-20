<div class="widget">
	<div class="panel">
		<div class="section-subtitle-wrapper" role="tab" id="common-friends-visitor">
			<h3><a data-toggle="collapse" href="#common-friends-collapse">{{$desc}}</a></h3>
		</div>
		<div id="common-friends-collapse" class="collapse" role="tabpanel" aria-labelledby="common-friends-visitor">
			{{if $items}}
			{{foreach $items as $item}}
			<div class="profile-match-wrapper">
				<div class="profile-match-photo">
					<a href="{{$base}}/chanview?f=&url={{$item.xchan_url}}">
						<img src="{{$item.xchan_photo_m}}" width="80" height="80" alt="{{$item.xchan_name}}" title="{{$item.xchan_name}}" />
					</a>
				</div>
				<div class="profile-match-break"></div>
				<div class="profile-match-name">
					<a href="{{$base}}/chanview?f=&url={{$item.xchan_url}}" title="{{$item.xchan_name}}">{{$item.xchan_name}}</a>
				</div>
				<div class="profile-match-end"></div>
			</div>
			{{/foreach}}
			{{/if}}
			<div id="rfic-end" class="clear"></div>
			{{if $linkmore}}<button class="btn btn-default"><a href="{{$base}}/common/{{$uid}}">{{$more}}</a></button>{{/if}}
		</div>
	</div>
</div>

