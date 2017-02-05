{{if $nav.login && !$userinfo}}
<div id="nav-login" class="modal" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header"><h3>{{$nav.loginmenu.1.1}}</h3></div>
			<div class="modal-body">
				<div class="form-group">
					{{$nav.login}}
					{{$nav.remote_login}}
				</div>
			</div>
		</div>
	</div>
</div>
{{/if}}
