<?php
	function utfeed_load_widget() {
		register_widget( 'utfeed_widget' );
	}
	
	function utfeed_pre($obj){
		echo "<pre>";
		print_r($obj);
		echo "</pre>";
	}