<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$title}}</h2>
	</div>
	<div class="section-content-danger-wrapper" id="rename-channel-desc">
		<strong>{{$desc.0}}</strong><strong>{{$desc.1}}</strong>
	</div>
	<div class="section-content-tools-wrapper">
		<form action="{{$basedir}}/changeaddr" autocomplete="off" method="post" >
			<input type="hidden" name="verify" value="{{$hash}}" />
			<div class="form-group" id="rename-channel-pass-wrapper">
				<label id="rename-channel-pass-label" for="rename-channel-pass">{{$passwd}}</label>
				<input class="form-control" type="password" id="rename-channel-pass" autocomplete="off" name="qxz_password" value=" " />
			</div>
			{{include file="field_input.tpl" field=$newname}}
			<button type="submit" name="submit" class="btn btn-danger">{{$submit}}</button>
		</form>
	</div>
</div>

