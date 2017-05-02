<div class="modal" id="item-filer-dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">{{$title}}</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        {{include file="field_combobox.tpl"}}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{$cancel}}</button>
        <button id="filer_save" type="button" class="btn btn-primary">{{$submit}}</button>
      </div>
    </div>
  </div>
</div>
