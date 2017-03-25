<style>
  .diff {
    width:100%;
    word-break: break-all;
  }

  .diff td{
    padding:0 0.667em;
    vertical-align:top;
    white-space:pre;
    white-space:pre-wrap;
    font-family:Consolas,'Courier New',Courier,monospace;
    font-size:1.0em;
    line-height:1.333;
  }

  .diff span{
    display:block;
    min-height:1.333em;
    margin-top:-1px;
    padding:0 3px;
  }

  * html .diff span{
    height:1.333em;
  }

  .diff span:first-child{
    margin-top:0;
  }

  .diffDeleted span{
    border:1px solid rgb(255,192,192);
    background:rgb(255,224,224);
  }

  .diffInserted span{
    border:1px solid rgb(192,255,192);
    background:rgb(224,255,224);
  }
</style>
<table class="" style="width: 100%;">
  {{foreach $pageHistory as $commit}}
  <tr class="wikis-index-row"><td>
      <table id="rev-{{$commit.revision}}" onclick="$('#details-{{$commit.revision}}').show()" style="width: 100%;">
        <tr><td width="10%">Date</td><td width="70%">{{$commit.date}}</td>
            <td rowspan="3" width="20%" align="right">
		{{if $permsWrite}}
              <button id="revert-{{$commit.revision}}" class="btn btn-danger btn-sm" onclick="wiki_revert_page('{{$commit.revision}}')">Revert</button>
              <br><br>
		{{/if}}
              <button id="compare-{{$commit.revision}}" class="btn btn-warning btn-sm" onclick="wiki_compare_page('{{$commit.revision}}')">Compare</button>
            </td></tr>
        <tr><td>{{$name_lbl}}</td><td>{{$commit.name}}</td></tr>
        <tr><td>{{$msg_label}}</td><td>{{$commit.title}}</td></tr>
      </table>
    </td></tr>
  {{/foreach}}          
</table>
