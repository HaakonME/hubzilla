<?php

function nojs_init(&$a) {

	setcookie('jsdisabled', 1, 0);
	$p = $_GET['query'];
	goaway(z_root() . (($p) ? '/' . $p : ''));

}