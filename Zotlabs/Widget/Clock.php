<?php

namespace Zotlabs\Widget;

class Clock {

	function widget($arr) {

		$miltime = 0;
		if(isset($arr['military']) && $arr['military'])
			$miltime = 1;

		$o = <<< EOT
<div class="widget">
<h3 class="clockface"></h3>
<script>

var timerID = null
var timerRunning = false

function stopclock(){
    if(timerRunning)
        clearTimeout(timerID)
    timerRunning = false
}

function startclock(){
    stopclock()
    showtime()
}

function showtime(){
    var now = new Date()
    var hours = now.getHours()
    var minutes = now.getMinutes()
    var seconds = now.getSeconds()
	var military = $miltime
    var timeValue = ""
	if(military)
		timeValue = hours
	else
		timeValue = ((hours > 12) ? hours - 12 : hours)
    timeValue  += ((minutes < 10) ? ":0" : ":") + minutes
//    timeValue  += ((seconds < 10) ? ":0" : ":") + seconds
	if(! military)
	    timeValue  += (hours >= 12) ? " P.M." : " A.M."
    $('.clockface').html(timeValue)
    timerID = setTimeout("showtime()",1000)
    timerRunning = true
}

$(document).ready(function() {
	startclock();
});

</script>
</div>
EOT;

	return $o;
	}
}

