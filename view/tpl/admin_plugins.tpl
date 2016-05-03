<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			<button class="btn btn-success btn-xs" onclick="openClose('form');">{{$addrepo}}</button>
		</div>
		<h2 id="title">{{$title}} - {{$page}}</h2>
		<div class="clear"></div>
	</div>
	<div id="form" class="section-content-tools-wrapper"{{if !$expandform}} style="display:none;"{{/if}}>
		{{$form}}
	</div>
	<div class="clear"></div>
    <div id="chat-rotator-wrapper" class="center-block">
        <div id="chat-rotator"></div>
    </div>
    <div class="clear"></div>
    <div id="new-repo-info" class="section-content-wrapper"></div>
	<div class="section-content-wrapper-np">
      {{foreach $plugins as $p}}
      <div class="section-content-tools-wrapper" id="pluginslist">		
		<div class="contact-info plugin {{$p.1}}">
            {{if ! $p.2.disabled}}				
            <a class='toggleplugin' href='{{$baseurl}}/admin/{{$function}}/{{$p.0}}?a=t&amp;t={{$form_security_token}}' title="{{if $p.1==on}}Disable{{else}}Enable{{/if}}" ><i class='fa {{if $p.1==on}}fa-check-square-o{{else}}fa-square-o{{/if}} admin-icons'></i></a>
            {{else}}
            <i class='fa fa-stop admin-icons'></i>
            {{/if}}
            <a href='{{$baseurl}}/admin/{{$function}}/{{$p.0}}'><span class='name'>{{$p.2.name}}</span></a> - <span class="version">{{$p.2.version}}</span>{{if $p.2.disabled}} {{$disabled}}{{/if}}
            {{if $p.2.experimental}} {{$experimental}} {{/if}}{{if $p.2.unsupported}} {{$unsupported}} {{/if}}

            <div class='desc'>{{$p.2.description}}</div>
		</div>
	</div>
    {{/foreach}}
      
	</div>
</div>

<script>
  function adminPluginsAddRepo() {
      var repoURL = $('#id_repoURL').val();
      $('#chat-rotator').spin('tiny');
      $.post(
        "/admin/plugins/addrepo", {repoURL: repoURL}, 
            function(response) {
                $('#chat-rotator').spin(false);
                if (response.success) {
                  $('#new-repo-info').html('<h3>Repo Info</h3><p>The repo was cloned to<br>' + response.message + '</p>');
                } else {
                    window.console.log('Error adding repo :' + response['message']);
                }
                return false;
            },
        'json');
  }
</script>