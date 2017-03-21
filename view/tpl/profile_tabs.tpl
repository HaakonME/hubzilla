{{foreach $tabs as $tab}}
<a class="dropdown-item{{if $tab.sel}} {{$tab.sel}}{{/if}}" href="{{$tab.url}}"{{if $tab.title}} title="{{$tab.title}}"{{/if}}>{{$tab.label}}</a>
{{/foreach}}
<div class="dropdown-divider"></div>
