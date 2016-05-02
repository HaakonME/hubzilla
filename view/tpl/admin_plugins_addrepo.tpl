<form id="add-plugin-repo-form" action="{{$post}}" method="post" >

    <p class="descriptive-text">{{$desc}}</p>
    {{include file="field_input.tpl" field=$repoURL}}
    <div class="btn-group pull-right">
		<button id="add-plugin-repo-submit" class="btn btn-primary" type="submit" name="submit">{{$submit}}</button>
	</div>
</form>
