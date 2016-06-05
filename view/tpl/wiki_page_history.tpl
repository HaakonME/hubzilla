<table class="table-striped table-responsive table-hover" style="width: 100%;">
  {{foreach $pageHistory as $commit}}
  <tr><td>
      <table id="rev-{{$commit.hash}}" onclick="$('#details-{{$commit.hash}}').show()">
        <tr><td>Date</td><td>{{$commit.date}}</td><td rowspan="3"">
            <button id="revert-{{$commit.hash}}" class="btn btn-warning btn-xs" onclick="wiki_revert_page('{{$commit.hash}}')">Revert</button></td></tr>
        <tr><td>Name</td><td>{{$commit.name}}</td></tr>
        <tr><td>Message</td><td>{{$commit.title}}</td></tr>
      </table>
    </td></tr>
  {{/foreach}}          
</table>