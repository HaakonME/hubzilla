{{if $new}}
<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<h2>{{$header}}</h2>
	</div>
	<div class="section-content-wrapper">
{{/if}}
		<div id="prvmail-wrapper" >
			<form id="prvmail-form" action="mail" method="post" >

				<input type="hidden" id="inp-prvmail-expires" name="expires" value="{{$defexpire}}" />
				<input type="hidden" name="media_str" id="jot-media" value="" />

				{{if $new}}
				<div class="form-group">
					<label for="recip">{{$to}}</label>
					<input class="form-control" type="text" id="recip" name="messagerecip" value="{{$prefill}}" maxlength="255" />
					<input type="hidden" id="recip-complete" name="messageto" value="{{$preid}}">
				</div>
				<div class="form-group">
					<label for="prvmail-subject">{{$subject}}</label>
					<input class="form-control" type="text" maxlength="255" id="prvmail-subject" name="subject" value="{{$subjtxt}}" />
				</div>
				{{/if}}

				{{if $reply}}
				<input type="hidden" name="replyto" value="{{$parent}}" />
				<input type="hidden" name="messageto" value="{{$recphash}}" />
				<input type="hidden" name="subject" value="{{$subjtxt}}" />
				{{/if}}

				<div class="form-group">
					<label for="prvmail-text">{{$yourmessage}}</label>
					<textarea class="form-control" id="prvmail-text" name="body"">{{$text}}</textarea>
				</div>

				<div id="prvmail-submit-wrapper" class="form-group">
					<div id="prvmail-submit" class="pull-right">
						<button class="btn btn-primary btn-sm" type="submit" id="prvmail-submit" name="submit" value="{{$submit}}">{{$submit}}</button>
					</div>
					<div id="prvmail-tools" class="btn-group pull-left">
						<button id="prvmail-attach-wrapper" class="btn btn-default btn-sm" >
							<i id="prvmail-attach" class="icon-paper-clip jot-icons" title="{{$attach}}"></i>
						</button>
						<button id="prvmail-link-wrapper" class="btn btn-default btn-sm" onclick="prvmailJotGetLink(); return false;" >
							<i id="prvmail-link" class="icon-link jot-icons" title="{{$insert}}" ></i>
						</button>
						{{if $feature_expire}}
						<button id="prvmail-expire-wrapper" class="btn btn-default btn-sm" onclick="prvmailGetExpiry();return false;" >
							<i id="prvmail-expires" class="icon-eraser jot-icons" title="{{$expires}}" ></i>
						</button>
						{{/if}}
						{{if $feature_encrypt}}
						<button id="prvmail-encrypt-wrapper" class="btn btn-default btn-sm" onclick="red_encrypt('{{$cipher}}','#prvmail-text',$('#prvmail-text').val());return false;">
							<i id="prvmail-encrypt" class="icon-key jot-icons" title="{{$encrypt}}" ></i>
						</button>
						{{/if}}
					</div>
					<div id="prvmail-rotator-wrapper" class="pull-left">
						<div id="prvmail-rotator"></div>
					</div>
					<div class="clear"></div>
				</div>
			</form>
		</div>
{{if $new}}
	</div>
</div>
{{/if}}
