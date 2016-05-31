<table class="table-striped table-responsive table-hover" style="width: 100%;">
  {{foreach $pageHistory as $commit}}
  <tr><td>
      <table>
        <tr><td>Date</td><td>{{$commit.date}}</td></tr>
        <tr><td>Name</td><td>{{$commit.name}}</td></tr>
        <tr><td>Message</td><td>{{$commit.title}}</td></tr>
      </table>
    </td></tr>
  {{/foreach}}          
</table>