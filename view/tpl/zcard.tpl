<style>
.hz_card {
	-moz-transform: translate(-{{$translate}}%, -{{$translate}}%) scale({{$scale}}, {{$scale}}); 
	transform: translate(-{{$translate}}%, -{{$translate}}%) scale({{$scale}}, {{$scale}}); 
}
.hz_cover_photo {
	max-width: 100%;
}
.hz_profile_photo {
	position: relative;
	top: -300px;
	left: 30px;
	background-color: white;
	border: 1px solid #ddd;
	border-radius: 5px;
	-moz-border-radius: 5px;
	padding: 10px;
	width: 320px;
	height: 320px;
}

.hz_name {
	position: relative;
	top: -100px;
	left: 400px;
	color: #fff;
	font-size: 48px;
    text-rendering: optimizelegibility;
    text-shadow: 0 0 3px rgba(0, 0, 0, 0.8);
}
.hz_addr {
	position: relative;
	top: -110px;
	left: 400px;
	color: #fff;
	font-size: 24px;
    text-rendering: optimizelegibility;
    text-shadow: 0 0 3px rgba(0, 0, 0, 0.8);
}	

</style>

<div class="hz_card">
	<div class="hz_cover_photo"><img src="{{$cover.href}}" alt="{{$zcard.chan.xchan_name}}" />
		<div class="hz_name">{{$zcard.chan.xchan_name}}</div>
		<div class="hz_addr">{{$zcard.chan.channel_addr}}</div>
	</div>
	<div class="hz_profile_photo"><img style="width: 300px; height: 300px;" src="{{$pphoto.href}}" alt="{{$zcard.chan.xchan_name}}" /></div>
</div>

