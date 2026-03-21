<?php
class UTFEED_Twitter
{
	private static $has_embed = false;

	public static function has_embed() {
		return self::$has_embed;
	}

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

		$api_html = $this->getTwitterHtmlFromApi();
		if ( ! empty( $api_html ) ) {
			return $api_html;
		}
		self::$has_embed = true;

		$width = isset($this->width) ? absint($this->width) : 400;
		$height = isset($this->height) ? absint($this->height) : 600;
		$theme = (isset($this->theme) && in_array($this->theme, array('light', 'dark'), true)) ? $this->theme : 'dark';
		$track = (!empty($this->tracking) && $this->tracking === 'on');
		$feed_lang = isset($this->feed_lang) ? sanitize_text_field($this->feed_lang) : '';
		$actual_handle = isset($this->actual_handle) ? esc_url_raw($this->actual_handle) : esc_url_raw($this->handle);
		$tag = 'a';
		$content = $href = "";
		$class = 'twitter-timeline';

		switch ($this->feed_type) {
			case "profile":
				$href = $actual_handle;
				$content = __(UTFEED_PLUGIN_TWITTER_IS_LOADING, 'ultimate-twitter-feeds');
				break;
			case "list":
				$href = $actual_handle;
				$content = __(UTFEED_PLUGIN_TWITTER_IS_LOADING, 'ultimate-twitter-feeds');
				break;
			case "single_tweet":
				$tag = 'blockquote';
				$class = 'twitter-tweet';
				$href = '';
				$content = "<p lang='en' dir='ltr'><a href='" . esc_url($actual_handle) . "'>" . esc_html__(UTFEED_PLUGIN_TWITTER_IS_LOADING, 'ultimate-twitter-feeds') . "</a></p>";
				break;
		}

		$attrs = array(
			'class' => $class,
			'data-width' => (string) $width,
			'data-height' => (string) $height,
			'data-theme' => $theme,
			'href' => $href,
		);
		if ( ! empty( $feed_lang ) ) {
			$attrs['data-lang'] = $feed_lang;
		}
		if ( $track ) {
			$attrs['data-dnt'] = 'true';
		}

		$html = '<' . esc_attr( $tag );
		foreach ( $attrs as $name => $value ) {
			$html .= ' ' . esc_attr( $name ) . "='" . ( 'href' === $name ? esc_url( $value ) : esc_attr( $value ) ) . "'";
		}
		$html .= '>' . $content . '</' . esc_attr( $tag ) . '>';

		return $html;
	}

	private function getTwitterHtmlFromApi() {
		$token = $this->getBearerToken();
		if ( empty( $token ) ) {
			return '';
		}

		switch ( $this->feed_type ) {
			case 'profile':
				return $this->renderProfileFromApi( $token );
			case 'single_tweet':
				return $this->renderSingleTweetFromApi( $token );
			default:
				return '';
		}
	}

	private function getBearerToken() {
		if ( function_exists( 'utfeed_get_api_token' ) ) {
			$token = utfeed_get_api_token();
			if ( ! empty( $token ) ) {
				return $token;
			}
		}
		return '';
	}

	private function getUsernameFromHandle() {
		$raw = trim( (string) $this->handle );
		$path = parse_url( $raw, PHP_URL_PATH );
		$value = ! empty( $path ) ? $path : $raw;
		$value = trim( $value, "/ \t\n\r\0\x0B" );
		$parts = explode( '/', $value );
		$username = isset( $parts[0] ) ? $parts[0] : '';
		$username = ltrim( $username, '@' );
		return preg_replace( '/[^A-Za-z0-9_]/', '', $username );
	}

	private function getTweetIdFromHandle() {
		$raw = trim( (string) $this->handle );
		if ( preg_match( '/\/status\/([0-9]+)/', $raw, $m ) ) {
			return $m[1];
		}
		if ( preg_match( '/^[0-9]+$/', $raw ) ) {
			return $raw;
		}
		return '';
	}

	private function xApiGet( $path, $token, $query = array() ) {
		$url = add_query_arg( $query, 'https://api.x.com/2/' . ltrim( $path, '/' ) );
		$cache_key = 'utfeed_api_' . md5( $url . '|' . substr( $token, 0, 16 ) );
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			set_transient(
				'utfeed_api_debug',
				array(
					'time' => time(),
					'url' => $url,
					'status' => 'wp_error',
					'message' => $response->get_error_message(),
				),
				5 * MINUTE_IN_SECONDS
			);
			return null;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			$body = wp_remote_retrieve_body( $response );
			set_transient(
				'utfeed_api_debug',
				array(
					'time' => time(),
					'url' => $url,
					'status' => $status,
					'body' => is_string( $body ) ? substr( $body, 0, 2000 ) : '',
				),
				5 * MINUTE_IN_SECONDS
			);
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );
		if ( ! is_array( $json ) ) {
			set_transient(
				'utfeed_api_debug',
				array(
					'time' => time(),
					'url' => $url,
					'status' => 'invalid_json',
					'body' => is_string( $body ) ? substr( $body, 0, 2000 ) : '',
				),
				5 * MINUTE_IN_SECONDS
			);
			return null;
		}

		set_transient( $cache_key, $json, 5 * MINUTE_IN_SECONDS );
		return $json;
	}

	private function renderProfileFromApi( $token ) {
		$username = $this->getUsernameFromHandle();
		if ( empty( $username ) ) {
			return '';
		}

		$user = $this->xApiGet(
			'users/by/username/' . rawurlencode( $username ),
			$token,
			array(
				'user.fields' => 'name,username,profile_image_url',
			)
		);
		if ( empty( $user['data']['id'] ) ) {
			return '';
		}

		$user_data = $user['data'];
		$tweets = $this->xApiGet(
			'users/' . rawurlencode( $user_data['id'] ) . '/tweets',
			$token,
			array(
				'max_results' => 5,
				'exclude' => 'retweets,replies',
				'tweet.fields' => 'created_at,public_metrics',
			)
		);
		if ( empty( $tweets['data'] ) || ! is_array( $tweets['data'] ) ) {
			return '';
		}

		$theme_class = ( isset( $this->theme ) && 'dark' === $this->theme ) ? 'utfeed-api-feed--dark' : 'utfeed-api-feed--light';
		$html = '<div class="utfeed-api-feed ' . esc_attr( $theme_class ) . '">';
		$html .= '<div class="utfeed-api-feed__header"><strong>@' . esc_html( $user_data['username'] ) . '</strong></div>';
		$html .= '<ul class="utfeed-api-feed__list">';
		foreach ( $tweets['data'] as $tweet ) {
			$text = isset( $tweet['text'] ) ? $tweet['text'] : '';
			$tweet_url = trailingslashit( UTFEED_PLUGIN_TWITTER_URL_ALT ) . rawurlencode( $user_data['username'] ) . '/status/' . rawurlencode( $tweet['id'] );
			$html .= '<li class="utfeed-api-feed__item">';
			$html .= '<p>' . nl2br( esc_html( $text ) ) . '</p>';
			$html .= '<p><a href="' . esc_url( $tweet_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View on X', 'ultimate-twitter-feeds' ) . '</a></p>';
			$html .= '</li>';
		}
		$html .= '</ul></div>';
		$html .= $this->getApiFeedStyles();

		return $html;
	}

	private function renderSingleTweetFromApi( $token ) {
		$tweet_id = $this->getTweetIdFromHandle();
		if ( empty( $tweet_id ) ) {
			return '';
		}

		$data = $this->xApiGet(
			'tweets/' . rawurlencode( $tweet_id ),
			$token,
			array(
				'expansions' => 'author_id',
				'tweet.fields' => 'created_at,public_metrics,author_id',
				'user.fields' => 'username,name,profile_image_url',
			)
		);
		if ( empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return '';
		}

		$tweet = $data['data'];
		$username = '';
		if ( ! empty( $data['includes']['users'][0]['username'] ) ) {
			$username = $data['includes']['users'][0]['username'];
		}

		$tweet_url = ! empty( $username )
			? trailingslashit( UTFEED_PLUGIN_TWITTER_URL_ALT ) . rawurlencode( $username ) . '/status/' . rawurlencode( $tweet['id'] )
			: trailingslashit( UTFEED_PLUGIN_TWITTER_URL_ALT ) . 'i/web/status/' . rawurlencode( $tweet['id'] );

		$theme_class = ( isset( $this->theme ) && 'dark' === $this->theme ) ? 'utfeed-api-feed--dark' : 'utfeed-api-feed--light';
		$html = '<div class="utfeed-api-feed ' . esc_attr( $theme_class ) . '">';
		$html .= '<div class="utfeed-api-feed__item">';
		$html .= '<p>' . nl2br( esc_html( $tweet['text'] ) ) . '</p>';
		$html .= '<p><a href="' . esc_url( $tweet_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View on X', 'ultimate-twitter-feeds' ) . '</a></p>';
		$html .= '</div></div>';
		$html .= $this->getApiFeedStyles();

		return $html;
	}

	private function getApiFeedStyles() {
		static $printed = false;
		if ( $printed ) {
			return '';
		}
		$printed = true;
		return "<style>.utfeed-api-feed{border:1px solid #d0d7de;border-radius:12px;padding:12px;max-width:100%;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif}.utfeed-api-feed--dark{background:#111;color:#f5f5f5;border-color:#2f3336}.utfeed-api-feed--light{background:#fff;color:#111}.utfeed-api-feed__header{margin-bottom:8px}.utfeed-api-feed__list{list-style:none;padding:0;margin:0}.utfeed-api-feed__item{padding:10px 0;border-top:1px solid rgba(127,127,127,.25)}.utfeed-api-feed__item:first-child{border-top:0}.utfeed-api-feed a{color:#1d9bf0;text-decoration:none}</style>";
	}

	public function cleanTwitterHandle()
	{
		if (empty($this->handle)) {
			return '';
		}
		$raw_handle = trim((string) $this->handle);
		$parsed_path = parse_url($raw_handle, PHP_URL_PATH);
		$this->handle = !empty($parsed_path) ? $parsed_path : $raw_handle;
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
		$original_url = $url;
		$primary_url = add_query_arg(
			array(
				'url' => $original_url,
			),
			UTFEED_PLUGIN_TWITTER_OEMBED_URL
		);
		$response = wp_remote_get(
			$primary_url,
			array(
				'timeout' => 10,
			)
		);
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$legacy_url = add_query_arg(
				array(
					'url' => $original_url,
				),
				UTFEED_PLUGIN_TWITTER_OEMBED_URL_LEGACY
			);
			$response = wp_remote_get(
				$legacy_url,
				array(
					'timeout' => 10,
				)
			);
			if ( is_wp_error( $response ) ) {
				return $original_url;
			}
		}

		$result     = wp_remote_retrieve_body($response);
		$status		= wp_remote_retrieve_response_code( $response );
		if($status === 200){
			$obj = json_decode($result);
			if ( !empty($obj->url) ) {
				return $obj->url;
			}
			return $original_url;
		}
		else {
			return $original_url;
		}
	}
}
