<?php
class UTFEED_Widgets extends WP_Widget {
	private $defaultValues = [];
	
	protected function __construct( $name, $title, $parameters) {
		parent::__construct($name, $title, $parameters);
		$this->defaultValues['title'] = UTFEED_PLUGIN_TITLE;
		$this->defaultValues['handle'] = 'TwitterDev';
		$this->defaultValues['actual_handle'] = 'TwitterDev';
		$this->defaultValues['feed_lang'] = '';
		$this->defaultValues['feed_width'] = 350;
		$this->defaultValues['feed_height'] = 600;
		$this->defaultValues['feed_theme'] = 'light';
	}
	
	protected function GetWidgetTitle( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
		
		$title .= $args['before_widget'];
		
		if ( ! empty( $title ) ){
			$title = $args['before_title'] . $title . $args['after_title'];
		}
		else {
			$title = __(UTFEED_PLUGIN_TITLE, 'ultimate-twitter-feeds' );	
		}
		
		$title .= $args['after_widget'];
		return apply_filters( 'utfeed_widget_title', $title );
	}
	
	protected function GetWidgetHtml( $i ) {
		$twitter = new UTFEED_Twitter();
		
		$twitter->width = $i['feed_width'];
		$twitter->height = $i['feed_height'];
		$twitter->theme = $i['feed_theme'];
		$twitter->handle = $i['handle'];
		$twitter->actual_handle = $i['actual_handle'];
		$twitter->feed_type = $i['feed_type'];
		$twitter->tracking = $i['feed_track'];
		$twitter->feed_lang = $i['feed_lang'];
		
		return $twitter->getTwitterHtml();
	}
	
	private function CleanValue( $value ){
		return ( ! empty( $value ) ) ? strip_tags( $value ) : '';
	}
	
	protected function UpdateWidgetInstace( $ni, $oi ){
		$i = [];
		$i['title'] = $this->CleanValue( $ni['title'] );
		$i['feed_type'] = $this->CleanValue( $ni['feed_type'] );
		$i['handle'] =  $this->CleanValue( $ni['handle'] );
		$i['actual_handle'] =  $this->CleanHandle( $i['handle'], $i['feed_type'] );
		$i['feed_lang'] = $this->CleanValue( $ni['feed_lang'] );
		$i['feed_width'] = $this->CleanValue( $ni['feed_width'] );
		$i['feed_height'] = $this->CleanValue( $ni['feed_height'] );
		$i['feed_theme'] = $this->CleanValue( $ni['feed_theme'] );
		$i['feed_track'] = $this->CleanValue( $ni['feed_track'] );
		return $i;
	}
	
	private function CleanHandle($handle, $type){
		$twitter = new UTFEED_Twitter();
		$twitter->handle = $handle;
		$twitter->feed_type = $type;
		$twitter->cleanTwitterHandle();
		return $twitter->actual_handle;
	}
	
	protected function GetInstanceValues( $i ) {
		$values = [];
		foreach($this->defaultValues as $key => $value) {
			$values[$key] = isset( $i[ $key ] ) ? $i[ $key ] : $value;
		}
		return $values;
	}
}