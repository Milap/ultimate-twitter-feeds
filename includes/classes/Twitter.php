<?php
class UTFEED_Twitter
{

	public $width;
	public $height;
	public $theme;
	public $handle;
	public $actual_handle;
	public $feed_type;
	public $feed_lang;
	public $tracking;

	public static function getTwitterFeedTypes()
	{
		return [
			['profile', 'Profile'],
			['list', 'List'],
			['single_tweet', 'Single Tweet']
		];
	}

	public static function getTwitterFeedLangs()
	{
		return [
			['', 'Automatic'],
			['en', 'English'],
			['ar', 'Arabic'],
			['bn', 'Bengali'],
			['zh-cn', 'Chinese (Simplified)'],
			['zh-tw', 'Chinese (Traditional)'],
			['cs', 'Czech'],
			['da', 'Danish'],
			['nl', 'Dutch'],
			['fil', 'Filipino'],
			['fi', 'Finnish'],
			['fr', 'French'],
			['de', 'German'],
			['el', 'Greek'],
			['he', 'Hebrew'],
			['hi', 'Hindi'],
			['hu', 'Hungarian'],
			['id', 'Indonesian'],
			['it', 'Italian'],
			['ja', 'Japanese'],
			['ko', 'Korean'],
			['msa', 'Malay'],
			['no', 'Norwegian'],
			['fa', 'Persian'],
			['pl', 'Polish'],
			['pt', 'Portuguese'],
			['ro', 'Romanian'],
			['ru', 'Russian'],
			['es', 'Spanish'],
			['sv', 'Swedish'],
			['th', 'Thai'],
			['tr', 'Turkish'],
			['uk', 'Ukrainian'],
			['ur', 'Urdu'],
			['vi', 'Vietnamese']
		];
	}

	public function getTwitterHtml()
	{
		if (empty($this->handle) || empty($this->feed_type)) {
			return '';
		}

		$width = isset($this->width) ? $this->width : 400;
		$height = isset($this->height) ? $this->height : 600;
		$theme = isset($this->theme) ? $this->theme : 'dark';
		$track = isset($this->tracking) ? $this->tracking : 'n';
		$feed_lang = isset($this->feed_lang) ? $this->feed_lang : 'en';
		$actual_handle = isset($this->actual_handle) ? $this->actual_handle : $this->handle;
		$tag = 'a';
		$content = $href = "";
		$class = 'twitter-timeline';

		switch ($this->feed_type) {
			case "profile":
				$href = $this->actual_handle;
				$content = __(UTFEED_PLUGIN_TWITTER_IS_LOADING, 'ultimate-twitter-feeds');
				break;
			case "list":
				$href = $this->actual_handle;
				$content = __(UTFEED_PLUGIN_TWITTER_IS_LOADING, 'ultimate-twitter-feeds');
				break;
			case "single_tweet":
				$tag = 'blockquote';
				$class = 'twitter-tweet';
				$href = '';
				$content = "<p lang='en' dir='ltr'><a href='" . esc_attr($this->actual_handle) . "'>" . __(UTFEED_PLUGIN_TWITTER_IS_LOADING, 'ultimate-twitter-feeds') . "</a></p>";
				break;
		}

		$html = sprintf("<%s class='%s' data-lang='%s' data-width='%d' data-height='%d' data-dnt='%s' data-theme='%s' href='%s'>%s</%s>", $tag, $class, $feed_lang, $width, $height, $track, $theme, $href, $content, $tag);

		$html .= '<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>';
		return $html;
	}

	public function cleanTwitterHandle()
	{
		if (empty($this->handle)) {
			return '';
		}
		$this->handle = parse_url($this->handle, PHP_URL_PATH);
		$this->handle = ltrim($this->handle, '/');
		$this->handle = ltrim($this->handle, '@');
		switch ($this->feed_type) {
			case 'list':
				$this->actual_handle = $this->getJsonFromTwitter(UTFEED_PLUGIN_TWITTER_URL . $this->handle);
				break;
			case 'profile':
				$this->actual_handle = UTFEED_PLUGIN_TWITTER_URL . $this->handle;
				break;
			case 'single_tweet':
				$this->actual_handle = UTFEED_PLUGIN_TWITTER_URL . $this->handle;
				break;
		}
	}
	private function getJsonFromTwitter($url)
	{
		$url = 'https://publish.twitter.com/oembed?url=' . $url;
		$response 	= wp_remote_get($url, array('sslverify' => FALSE));
		$result     = wp_remote_retrieve_body($response);
		$status		= $response['response']['code'];
		if($status === 200){
			$obj = json_decode($result);
			return $obj->url;
		}
		else {
			return $url;
		}
	}
}