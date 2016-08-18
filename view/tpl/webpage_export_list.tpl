<div class="generic-content-wrapper">
		<form action="" method="post" autocomplete="on" >
		<input type="hidden" name="action" value="{{$action}}">
		<div class="section-title-wrapper">
			<div class="pull-right">				
					<button class="btn btn-md btn-success" type="submit" name="submit" value="{{$exportbtn}}">{{$exportbtn}}</button>
			</div>
			<h2>{{$title}}</h2>
			<div class="clear"></div>
		</div>
		<div id="import-website-content-wrapper" class="section-content-wrapper">
						<div class="pull-left">
								<button id="toggle-select-all" class="btn btn-xs btn-primary" onclick="checkedAll(window.isChecked); return false;"><i class="fa fa-check"></i>&nbsp;Toggle Select All</button>
						</div>
						<div class="clear"></div>
				
		</div>
		<div class="clear"></div>
		</form>
</div>

