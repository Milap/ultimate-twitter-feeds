<?php
	function utfeed_load_widget() {
		register_widget( 'utfeed_widget' );
	}

	function utfeed_enqueue_assets() {
		wp_register_script(
			'utfeed-widgets-js',
			UTFEED_PLUGIN_TWITTER_WIDGETS_JS_LEGACY,
			array(),
			null,
			array(
				'in_footer' => true,
				'strategy'  => 'async',
			)
		);
		wp_enqueue_script( 'utfeed-widgets-js' );
	}

	function utfeed_get_block_defaults() {
		return array(
			'title' => UTFEED_PLUGIN_TITLE,
			'feed_type' => 'profile',
			'handle' => 'TwitterDev',
			'feed_width' => 350,
			'feed_height' => 600,
			'feed_theme' => 'light',
			'feed_lang' => '',
			'feed_track' => false,
		);
	}

	function utfeed_render_block( $attributes ) {
		$defaults = utfeed_get_block_defaults();
		$attrs = wp_parse_args( is_array( $attributes ) ? $attributes : array(), $defaults );

		$twitter = new UTFEED_Twitter();
		$twitter->width = absint( $attrs['feed_width'] );
		$twitter->height = absint( $attrs['feed_height'] );
		$twitter->theme = in_array( $attrs['feed_theme'], array( 'light', 'dark' ), true ) ? $attrs['feed_theme'] : 'light';
		$twitter->handle = sanitize_text_field( $attrs['handle'] );
		$twitter->feed_type = in_array( $attrs['feed_type'], array( 'profile', 'list', 'single_tweet' ), true ) ? $attrs['feed_type'] : 'profile';
		$twitter->tracking = ! empty( $attrs['feed_track'] ) ? 'on' : '';
		$twitter->feed_lang = sanitize_text_field( $attrs['feed_lang'] );
		$twitter->cleanTwitterHandle();

		$html = $twitter->getTwitterHtml();
		if ( empty( $html ) ) {
			return '';
		}

		$title = sanitize_text_field( $attrs['title'] );
		$output = '<div class="wp-block-ultimate-twitter-feeds-feed">';
		if ( ! empty( $title ) ) {
			$output .= '<h3 class="utfeed-block-title">' . esc_html( $title ) . '</h3>';
		}
		$output .= $html;
		$output .= '</div>';

		return $output;
	}

	function utfeed_register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$defaults = utfeed_get_block_defaults();
		$script_path = UTFEED_PLUGIN_ROOT . '/includes/assets/utfeed-block.js';
		$script_url = plugin_dir_url( __FILE__ ) . 'includes/assets/utfeed-block.js';
		$script_ver = file_exists( $script_path ) ? filemtime( $script_path ) : UTFEED_PLUGIN_VERSION;

		wp_register_script(
			'utfeed-block-editor',
			$script_url,
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n' ),
			$script_ver,
			true
		);

		register_block_type(
			'ultimate-twitter-feeds/feed',
			array(
				'api_version' => 2,
				'editor_script' => 'utfeed-block-editor',
				'render_callback' => 'utfeed_render_block',
				'attributes' => array(
					'title' => array( 'type' => 'string', 'default' => $defaults['title'] ),
					'feed_type' => array( 'type' => 'string', 'default' => $defaults['feed_type'] ),
					'handle' => array( 'type' => 'string', 'default' => $defaults['handle'] ),
					'feed_width' => array( 'type' => 'number', 'default' => $defaults['feed_width'] ),
					'feed_height' => array( 'type' => 'number', 'default' => $defaults['feed_height'] ),
					'feed_theme' => array( 'type' => 'string', 'default' => $defaults['feed_theme'] ),
					'feed_lang' => array( 'type' => 'string', 'default' => $defaults['feed_lang'] ),
					'feed_track' => array( 'type' => 'boolean', 'default' => $defaults['feed_track'] ),
				),
			)
		);
	}

	function utfeed_print_widgets_loader() {
		if ( ! class_exists( 'UTFEED_Twitter' ) || ! UTFEED_Twitter::has_embed() ) {
			return;
		}
		?>
		<script>
		window.twttr = (function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0], t = window.twttr || {};
			if (d.getElementById(id)) return t;
			js = d.createElement(s);
			js.id = id;
			js.src = "<?php echo esc_url( UTFEED_PLUGIN_TWITTER_WIDGETS_JS_LEGACY ); ?>";
			js.async = true;
			js.onerror = function () {
				js.src = "<?php echo esc_url( UTFEED_PLUGIN_TWITTER_WIDGETS_JS ); ?>";
			};
			if (fjs && fjs.parentNode) {
				fjs.parentNode.insertBefore(js, fjs);
			} else {
				(d.head || d.documentElement).appendChild(js);
			}
			t._e = [];
			t.ready = function(f) {
				t._e.push(f);
			};
			return t;
		}(document, "script", "twitter-wjs"));
		</script>
		<?php
	}

	function utfeed_get_settings_defaults() {
		return array(
			'client_id' => '',
			'client_secret' => '',
			'manual_bearer_token' => '',
			'authorize_url' => UTFEED_X_OAUTH_AUTHORIZE_URL,
			'token_url' => UTFEED_X_OAUTH_TOKEN_URL,
			'scopes' => 'tweet.read users.read offline.access',
			'access_token' => '',
			'refresh_token' => '',
			'expires_at' => 0,
			'token_type' => '',
			'connected_at' => 0,
		);
	}

	function utfeed_get_settings() {
		$raw = get_option( 'utfeed_settings', array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		return wp_parse_args( $raw, utfeed_get_settings_defaults() );
	}

	function utfeed_update_settings( $settings ) {
		update_option( 'utfeed_settings', $settings );
	}

	function utfeed_get_token_store() {
		$raw = get_option( 'utfeed_oauth_tokens', null );
		return is_array( $raw ) ? $raw : array();
	}

	function utfeed_save_token_store( $tokens ) {
		$tokens = is_array( $tokens ) ? $tokens : array();
		$existing = get_option( 'utfeed_oauth_tokens', null );
		if ( null === $existing ) {
			add_option( 'utfeed_oauth_tokens', $tokens, '', false );
		} else {
			update_option( 'utfeed_oauth_tokens', $tokens );
		}
	}

	function utfeed_clear_token_store() {
		delete_option( 'utfeed_oauth_tokens' );
	}

	function utfeed_has_access_token() {
		$tokens = utfeed_get_token_store();
		if ( ! empty( $tokens['access_token'] ) ) {
			return true;
		}
		$settings = utfeed_get_settings();
		return ! empty( $settings['access_token'] );
	}

	function utfeed_sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		$old = utfeed_get_settings();
		$new = utfeed_get_settings_defaults();

		$new['client_id'] = isset( $input['client_id'] ) ? sanitize_text_field( $input['client_id'] ) : '';
		$new['client_secret'] = isset( $input['client_secret'] ) ? trim( sanitize_text_field( $input['client_secret'] ) ) : '';
		if ( '' === $new['client_secret'] && ! empty( $old['client_secret'] ) ) {
			$new['client_secret'] = $old['client_secret'];
		}

		$new['manual_bearer_token'] = isset( $input['manual_bearer_token'] ) ? trim( sanitize_text_field( $input['manual_bearer_token'] ) ) : '';
		if ( '' === $new['manual_bearer_token'] && ! empty( $old['manual_bearer_token'] ) ) {
			$new['manual_bearer_token'] = $old['manual_bearer_token'];
		}

		$new['authorize_url'] = isset( $input['authorize_url'] ) ? esc_url_raw( $input['authorize_url'] ) : UTFEED_X_OAUTH_AUTHORIZE_URL;
		$new['token_url'] = isset( $input['token_url'] ) ? esc_url_raw( $input['token_url'] ) : UTFEED_X_OAUTH_TOKEN_URL;
		$new['scopes'] = isset( $input['scopes'] ) ? sanitize_text_field( $input['scopes'] ) : 'tweet.read users.read offline.access';

		foreach ( array( 'access_token', 'refresh_token', 'token_type' ) as $key ) {
			$new[ $key ] = isset( $old[ $key ] ) ? $old[ $key ] : '';
		}
		$new['expires_at'] = isset( $old['expires_at'] ) ? absint( $old['expires_at'] ) : 0;
		$new['connected_at'] = isset( $old['connected_at'] ) ? absint( $old['connected_at'] ) : 0;

		return $new;
	}

	function utfeed_register_settings() {
		register_setting(
			'utfeed_settings_group',
			'utfeed_settings',
			array(
				'type' => 'array',
				'sanitize_callback' => 'utfeed_sanitize_settings',
				'default' => utfeed_get_settings_defaults(),
			)
		);
	}

	function utfeed_add_admin_menu() {
		add_menu_page(
			__( 'Ultimate Twitter(X) Feeds', 'ultimate-twitter-feeds' ),
			__( 'Ultimate Twitter(X) Feeds', 'ultimate-twitter-feeds' ),
			'manage_options',
			'utfeed-settings',
			'utfeed_render_settings_page',
			utfeed_get_menu_icon(),
			58
		);
	}

	function utfeed_get_menu_icon() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 1227"><path fill="#ffffff" d="M714.16 519.284L1160.89 0H1055.06L667.137 450.887L357.328 0H0L468.492 681.821L0 1226.37H105.866L515.458 750.218L842.672 1226.37H1200L714.134 519.284H714.16ZM569.138 687.901L521.697 620.033L144.011 79.6944H306.615L611.488 515.941L658.929 583.809L1055.11 1150.64H892.503L569.138 687.928V687.901Z"/></svg>';
		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	function utfeed_get_settings_url( $notice = '' ) {
		$url = admin_url( 'options-general.php?page=utfeed-settings' );
		if ( ! empty( $notice ) ) {
			$url = add_query_arg( 'utfeed_notice', rawurlencode( $notice ), $url );
		}
		return $url;
	}

	function utfeed_get_oauth_redirect_uri() {
		return admin_url( 'admin-post.php?action=utfeed_x_oauth_callback' );
	}

	function utfeed_render_settings_notice() {
		if ( empty( $_GET['utfeed_notice'] ) ) {
			return;
		}

		$notice = sanitize_text_field( wp_unslash( $_GET['utfeed_notice'] ) );
		$messages = array(
			'connected' => array( 'class' => 'updated', 'text' => __( 'Connected to X successfully.', 'ultimate-twitter-feeds' ) ),
			'disconnected' => array( 'class' => 'updated', 'text' => __( 'Disconnected from X.', 'ultimate-twitter-feeds' ) ),
			'missing_client_id' => array( 'class' => 'error', 'text' => __( 'Please add Client ID before connecting.', 'ultimate-twitter-feeds' ) ),
			'invalid_state' => array( 'class' => 'error', 'text' => __( 'OAuth state validation failed. Please try again.', 'ultimate-twitter-feeds' ) ),
			'missing_code' => array( 'class' => 'error', 'text' => __( 'Authorization code was not returned by X.', 'ultimate-twitter-feeds' ) ),
			'missing_verifier' => array( 'class' => 'error', 'text' => __( 'OAuth code verifier expired. Please try connecting again.', 'ultimate-twitter-feeds' ) ),
			'token_exchange_failed' => array( 'class' => 'error', 'text' => __( 'Could not exchange auth code for token. Check app credentials and redirect URI.', 'ultimate-twitter-feeds' ) ),
			'access_denied' => array( 'class' => 'error', 'text' => __( 'Access was denied in X authorization flow.', 'ultimate-twitter-feeds' ) ),
		);

		if ( empty( $messages[ $notice ] ) ) {
			return;
		}

		echo '<div class="' . esc_attr( $messages[ $notice ]['class'] ) . '"><p>' . esc_html( $messages[ $notice ]['text'] ) . '</p></div>';
	}

	function utfeed_render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = utfeed_get_settings();
		$connect_url = wp_nonce_url( admin_url( 'admin-post.php?action=utfeed_x_oauth_start' ), 'utfeed_oauth_start' );
		$disconnect_url = wp_nonce_url( admin_url( 'admin-post.php?action=utfeed_x_oauth_disconnect' ), 'utfeed_oauth_disconnect' );
		$is_connected = utfeed_has_access_token();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Ultimate Twitter Feeds Settings', 'ultimate-twitter-feeds' ); ?></h1>
			<?php utfeed_render_settings_notice(); ?>
			<?php if ( isset( $_GET['utfeed_debug'] ) ) : ?>
				<?php
				$debug_settings = utfeed_get_settings();
				$debug_tokens = utfeed_get_token_store();
				$token_len = ! empty( $debug_settings['access_token'] ) ? strlen( $debug_settings['access_token'] ) : 0;
				$store_token_len = ! empty( $debug_tokens['access_token'] ) ? strlen( $debug_tokens['access_token'] ) : 0;
				$expires_at = ! empty( $debug_settings['expires_at'] ) ? absint( $debug_settings['expires_at'] ) : 0;
				$store_expires_at = ! empty( $debug_tokens['expires_at'] ) ? absint( $debug_tokens['expires_at'] ) : 0;
				$connected_at = ! empty( $debug_settings['connected_at'] ) ? absint( $debug_settings['connected_at'] ) : 0;
				$store_connected_at = ! empty( $debug_tokens['connected_at'] ) ? absint( $debug_tokens['connected_at'] ) : 0;
				$oauth_debug = get_transient( 'utfeed_oauth_debug' );
				$api_debug = get_transient( 'utfeed_api_debug' );
				$debug_build = filemtime( __FILE__ );
				?>
				<div class="notice notice-warning" style="padding:12px 16px;">
					<p><strong><?php esc_html_e( 'Debug: OAuth Storage', 'ultimate-twitter-feeds' ); ?></strong></p>
					<p><?php echo esc_html( 'Debug build (filemtime): ' . $debug_build ); ?></p>
					<p><?php echo esc_html( 'Access token length: ' . $token_len ); ?></p>
					<p><?php echo esc_html( 'Token store length: ' . $store_token_len ); ?></p>
					<p><?php echo esc_html( 'Token type: ' . ( ! empty( $debug_settings['token_type'] ) ? $debug_settings['token_type'] : '(empty)' ) ); ?></p>
					<p><?php echo esc_html( 'Token store type: ' . ( ! empty( $debug_tokens['token_type'] ) ? $debug_tokens['token_type'] : '(empty)' ) ); ?></p>
					<p><?php echo esc_html( 'Expires at (unix): ' . ( $expires_at ? $expires_at : '(empty)' ) ); ?></p>
					<p><?php echo esc_html( 'Token store expires at (unix): ' . ( $store_expires_at ? $store_expires_at : '(empty)' ) ); ?></p>
					<p><?php echo esc_html( 'Connected at (unix): ' . ( $connected_at ? $connected_at : '(empty)' ) ); ?></p>
					<p><?php echo esc_html( 'Token store connected at (unix): ' . ( $store_connected_at ? $store_connected_at : '(empty)' ) ); ?></p>
					<p><?php echo esc_html( 'Is multisite: ' . ( is_multisite() ? 'yes' : 'no' ) ); ?></p>
					<?php if ( is_array( $oauth_debug ) ) : ?>
						<p><strong><?php esc_html_e( 'Last OAuth Save Attempt', 'ultimate-twitter-feeds' ); ?></strong></p>
						<p><?php echo esc_html( 'Update result: ' . ( ! empty( $oauth_debug['update_result'] ) ? $oauth_debug['update_result'] : '(empty)' ) ); ?></p>
						<p><?php echo esc_html( 'Fallback result: ' . ( isset( $oauth_debug['fallback_result'] ) ? $oauth_debug['fallback_result'] : '(empty)' ) ); ?></p>
						<p><?php echo esc_html( 'Saved access length: ' . ( isset( $oauth_debug['saved_access_len'] ) ? $oauth_debug['saved_access_len'] : '(empty)' ) ); ?></p>
						<p><?php echo esc_html( 'Stored access length: ' . ( isset( $oauth_debug['stored_access_len'] ) ? $oauth_debug['stored_access_len'] : '(empty)' ) ); ?></p>
						<p><?php echo esc_html( 'Stored keys: ' . ( ! empty( $oauth_debug['stored_keys'] ) ? implode( ',', $oauth_debug['stored_keys'] ) : '(empty)' ) ); ?></p>
						<p><?php echo esc_html( 'DB error: ' . ( isset( $oauth_debug['db_error'] ) && '' !== $oauth_debug['db_error'] ? $oauth_debug['db_error'] : '(empty)' ) ); ?></p>
						<p><?php echo esc_html( 'DB last query: ' . ( isset( $oauth_debug['db_last_query'] ) && '' !== $oauth_debug['db_last_query'] ? $oauth_debug['db_last_query'] : '(empty)' ) ); ?></p>
						<p><?php echo esc_html( 'Debug time (unix): ' . ( ! empty( $oauth_debug['time'] ) ? $oauth_debug['time'] : '(empty)' ) ); ?></p>
					<?php endif; ?>
					<?php if ( is_array( $api_debug ) ) : ?>
						<p><strong><?php esc_html_e( 'Last X API Call', 'ultimate-twitter-feeds' ); ?></strong></p>
						<p><?php echo esc_html( 'Time (unix): ' . ( ! empty( $api_debug['time'] ) ? $api_debug['time'] : '(empty)' ) ); ?></p>
						<p><?php echo esc_html( 'Status: ' . ( isset( $api_debug['status'] ) ? $api_debug['status'] : '(empty)' ) ); ?></p>
						<p><?php echo esc_html( 'URL: ' . ( ! empty( $api_debug['url'] ) ? $api_debug['url'] : '(empty)' ) ); ?></p>
						<?php if ( ! empty( $api_debug['message'] ) ) : ?>
							<p><?php echo esc_html( 'Message: ' . $api_debug['message'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $api_debug['body'] ) ) : ?>
							<p><?php echo esc_html( 'Body: ' . $api_debug['body'] ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="notice notice-info" style="padding:12px 16px;">
				<p><strong><?php esc_html_e( 'How to get Client ID and Client Secret from X', 'ultimate-twitter-feeds' ); ?></strong></p>
				<ol style="margin-left:18px;">
					<li><?php esc_html_e( 'Go to developer portal: developer.x.com and sign in.', 'ultimate-twitter-feeds' ); ?></li>
					<li><?php esc_html_e( 'Create a Project and an App (or open an existing App).', 'ultimate-twitter-feeds' ); ?></li>
					<li><?php esc_html_e( 'Enable OAuth 2.0 for the App.', 'ultimate-twitter-feeds' ); ?></li>
					<li><?php esc_html_e( 'Add this Redirect URI in the X app settings:', 'ultimate-twitter-feeds' ); ?> <code><?php echo esc_html( utfeed_get_oauth_redirect_uri() ); ?></code></li>
					<li><?php esc_html_e( 'Copy the app Client ID and Client Secret into this page, then click Save Settings.', 'ultimate-twitter-feeds' ); ?></li>
					<li><?php esc_html_e( 'Click Connect with X and approve access.', 'ultimate-twitter-feeds' ); ?></li>
				</ol>
				<p><?php esc_html_e( 'Recommended OAuth scopes: tweet.read users.read offline.access', 'ultimate-twitter-feeds' ); ?></p>
			</div>
			<form method="post" action="options.php">
				<?php settings_fields( 'utfeed_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="utfeed-client-id"><?php esc_html_e( 'Client ID', 'ultimate-twitter-feeds' ); ?></label></th>
						<td><input id="utfeed-client-id" name="utfeed_settings[client_id]" type="text" class="regular-text" value="<?php echo esc_attr( $settings['client_id'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="utfeed-client-secret"><?php esc_html_e( 'Client Secret', 'ultimate-twitter-feeds' ); ?></label></th>
						<td>
							<input id="utfeed-client-secret" name="utfeed_settings[client_secret]" type="password" class="regular-text" value="" autocomplete="new-password" />
							<p class="description"><?php esc_html_e( 'Leave empty to keep existing secret.', 'ultimate-twitter-feeds' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="utfeed-manual-bearer"><?php esc_html_e( 'Manual Bearer Token', 'ultimate-twitter-feeds' ); ?></label></th>
						<td>
							<input id="utfeed-manual-bearer" name="utfeed_settings[manual_bearer_token]" type="password" class="regular-text" value="" autocomplete="new-password" />
							<p class="description"><?php esc_html_e( 'Optional fallback token. Leave empty to keep existing value.', 'ultimate-twitter-feeds' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="utfeed-scopes"><?php esc_html_e( 'Scopes', 'ultimate-twitter-feeds' ); ?></label></th>
						<td><input id="utfeed-scopes" name="utfeed_settings[scopes]" type="text" class="regular-text" value="<?php echo esc_attr( $settings['scopes'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="utfeed-authorize-url"><?php esc_html_e( 'Authorize URL', 'ultimate-twitter-feeds' ); ?></label></th>
						<td><input id="utfeed-authorize-url" name="utfeed_settings[authorize_url]" type="url" class="regular-text" value="<?php echo esc_attr( $settings['authorize_url'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="utfeed-token-url"><?php esc_html_e( 'Token URL', 'ultimate-twitter-feeds' ); ?></label></th>
						<td><input id="utfeed-token-url" name="utfeed_settings[token_url]" type="url" class="regular-text" value="<?php echo esc_attr( $settings['token_url'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Redirect URI', 'ultimate-twitter-feeds' ); ?></th>
						<td><code><?php echo esc_html( utfeed_get_oauth_redirect_uri() ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Connection Status', 'ultimate-twitter-feeds' ); ?></th>
						<td><?php echo $is_connected ? esc_html__( 'Connected', 'ultimate-twitter-feeds' ) : esc_html__( 'Not connected', 'ultimate-twitter-feeds' ); ?></td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 'ultimate-twitter-feeds' ) ); ?>
			</form>

			<p>
				<a class="button button-primary" href="<?php echo esc_url( $connect_url ); ?>"><?php esc_html_e( 'Connect with X', 'ultimate-twitter-feeds' ); ?></a>
				<a class="button" href="<?php echo esc_url( $disconnect_url ); ?>"><?php esc_html_e( 'Disconnect', 'ultimate-twitter-feeds' ); ?></a>
			</p>
		</div>
		<?php
	}

	function utfeed_base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	function utfeed_handle_oauth_start() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'ultimate-twitter-feeds' ) );
		}
		check_admin_referer( 'utfeed_oauth_start' );

		$settings = utfeed_get_settings();
		if ( empty( $settings['client_id'] ) ) {
			wp_safe_redirect( utfeed_get_settings_url( 'missing_client_id' ) );
			exit;
		}

		$state = wp_generate_password( 32, false, false );
		try {
			$verifier = utfeed_base64url_encode( random_bytes( 32 ) );
		} catch ( Exception $e ) {
			$verifier = utfeed_base64url_encode( wp_generate_password( 64, true, true ) );
		}
		$challenge = utfeed_base64url_encode( hash( 'sha256', $verifier, true ) );
		$user_id = get_current_user_id();

		set_transient( 'utfeed_oauth_state_' . $user_id, $state, 10 * MINUTE_IN_SECONDS );
		set_transient( 'utfeed_oauth_verifier_' . $user_id, $verifier, 10 * MINUTE_IN_SECONDS );

		$auth_url = ! empty( $settings['authorize_url'] ) ? $settings['authorize_url'] : UTFEED_X_OAUTH_AUTHORIZE_URL;
		$url = add_query_arg(
			array(
				'response_type' => 'code',
				'client_id' => $settings['client_id'],
				'redirect_uri' => utfeed_get_oauth_redirect_uri(),
				'scope' => $settings['scopes'],
				'state' => $state,
				'code_challenge' => $challenge,
				'code_challenge_method' => 'S256',
			),
			$auth_url
		);

		wp_redirect( esc_url_raw( $url ) );
		exit;
	}

	function utfeed_try_token_request( $url, $body ) {
		$headers = array(
			'Content-Type' => 'application/x-www-form-urlencoded',
		);
		if ( isset( $body['_auth_header'] ) && is_string( $body['_auth_header'] ) && '' !== $body['_auth_header'] ) {
			$headers['Authorization'] = $body['_auth_header'];
			unset( $body['_auth_header'] );
		}
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => $headers,
				'body' => $body,
			)
		);
		if ( is_wp_error( $response ) ) {
			return null;
		}
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return is_array( $data ) ? $data : null;
	}

	function utfeed_handle_oauth_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'ultimate-twitter-feeds' ) );
		}

		$user_id = get_current_user_id();
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
		$stored_state = get_transient( 'utfeed_oauth_state_' . $user_id );
		$verifier = get_transient( 'utfeed_oauth_verifier_' . $user_id );
		delete_transient( 'utfeed_oauth_state_' . $user_id );
		delete_transient( 'utfeed_oauth_verifier_' . $user_id );

		if ( isset( $_GET['error'] ) ) {
			wp_safe_redirect( utfeed_get_settings_url( 'access_denied' ) );
			exit;
		}
		if ( empty( $state ) || empty( $stored_state ) || ! hash_equals( $stored_state, $state ) ) {
			wp_safe_redirect( utfeed_get_settings_url( 'invalid_state' ) );
			exit;
		}

		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
		if ( empty( $code ) ) {
			wp_safe_redirect( utfeed_get_settings_url( 'missing_code' ) );
			exit;
		}
		if ( empty( $verifier ) ) {
			wp_safe_redirect( utfeed_get_settings_url( 'missing_verifier' ) );
			exit;
		}

		$settings = utfeed_get_settings();
		$body = array(
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => utfeed_get_oauth_redirect_uri(),
			'client_id' => $settings['client_id'],
			'code_verifier' => $verifier,
		);
		if ( ! empty( $settings['client_secret'] ) && ! empty( $settings['client_id'] ) ) {
			$body['_auth_header'] = 'Basic ' . base64_encode( $settings['client_id'] . ':' . $settings['client_secret'] );
		} elseif ( ! empty( $settings['client_secret'] ) ) {
			$body['client_secret'] = $settings['client_secret'];
		}

		$token_url = ! empty( $settings['token_url'] ) ? $settings['token_url'] : UTFEED_X_OAUTH_TOKEN_URL;
		$token = utfeed_try_token_request( $token_url, $body );
		if ( empty( $token ) ) {
			$token = utfeed_try_token_request( UTFEED_X_OAUTH_TOKEN_URL_LEGACY, $body );
		}
		if ( empty( $token['access_token'] ) ) {
			wp_safe_redirect( utfeed_get_settings_url( 'token_exchange_failed' ) );
			exit;
		}

		$settings['access_token'] = $token['access_token'];
		$settings['refresh_token'] = ! empty( $token['refresh_token'] ) ? $token['refresh_token'] : '';
		$settings['token_type'] = ! empty( $token['token_type'] ) ? $token['token_type'] : 'bearer';
		$settings['expires_at'] = ! empty( $token['expires_in'] ) ? ( time() + absint( $token['expires_in'] ) ) : 0;
		$settings['connected_at'] = time();

		$token_store = array(
			'access_token' => $settings['access_token'],
			'refresh_token' => $settings['refresh_token'],
			'token_type' => $settings['token_type'],
			'expires_at' => $settings['expires_at'],
			'connected_at' => $settings['connected_at'],
		);
		utfeed_save_token_store( $token_store );

		$update_result = update_option( 'utfeed_settings', $settings );
		$verify_settings = get_option( 'utfeed_settings', array() );
		$fallback_result = null;
		if ( ! $update_result && ( ! is_array( $verify_settings ) || empty( $verify_settings['access_token'] ) ) ) {
			delete_option( 'utfeed_settings' );
			$fallback_result = add_option( 'utfeed_settings', $settings, '', false );
			$verify_settings = get_option( 'utfeed_settings', array() );
		}
		$wpdb = isset( $GLOBALS['wpdb'] ) ? $GLOBALS['wpdb'] : null;
		$debug_payload = array(
			'time' => time(),
			'update_result' => $update_result ? 'true' : 'false',
			'fallback_result' => is_null( $fallback_result ) ? '(not_run)' : ( $fallback_result ? 'true' : 'false' ),
			'saved_access_len' => strlen( $settings['access_token'] ),
			'stored_access_len' => ( is_array( $verify_settings ) && ! empty( $verify_settings['access_token'] ) ) ? strlen( $verify_settings['access_token'] ) : 0,
			'stored_keys' => is_array( $verify_settings ) ? array_keys( $verify_settings ) : array(),
			'db_error' => ( $wpdb && ! empty( $wpdb->last_error ) ) ? $wpdb->last_error : '',
			'db_last_query' => ( $wpdb && ! empty( $wpdb->last_query ) ) ? $wpdb->last_query : '',
		);
		set_transient( 'utfeed_oauth_debug', $debug_payload, 10 * MINUTE_IN_SECONDS );

		wp_safe_redirect( utfeed_get_settings_url( 'connected' ) );
		exit;
	}

	function utfeed_refresh_access_token() {
		$settings = utfeed_get_settings();
		$tokens = utfeed_get_token_store();
		$refresh_token = ! empty( $tokens['refresh_token'] ) ? $tokens['refresh_token'] : $settings['refresh_token'];
		if ( empty( $refresh_token ) || empty( $settings['client_id'] ) ) {
			return false;
		}

		$body = array(
			'grant_type' => 'refresh_token',
			'refresh_token' => $refresh_token,
			'client_id' => $settings['client_id'],
		);
		if ( ! empty( $settings['client_secret'] ) && ! empty( $settings['client_id'] ) ) {
			$body['_auth_header'] = 'Basic ' . base64_encode( $settings['client_id'] . ':' . $settings['client_secret'] );
		} elseif ( ! empty( $settings['client_secret'] ) ) {
			$body['client_secret'] = $settings['client_secret'];
		}

		$token_url = ! empty( $settings['token_url'] ) ? $settings['token_url'] : UTFEED_X_OAUTH_TOKEN_URL;
		$token = utfeed_try_token_request( $token_url, $body );
		if ( empty( $token ) ) {
			$token = utfeed_try_token_request( UTFEED_X_OAUTH_TOKEN_URL_LEGACY, $body );
		}
		if ( empty( $token['access_token'] ) ) {
			return false;
		}

		$settings['access_token'] = $token['access_token'];
		if ( ! empty( $token['refresh_token'] ) ) {
			$settings['refresh_token'] = $token['refresh_token'];
		}
		$settings['token_type'] = ! empty( $token['token_type'] ) ? $token['token_type'] : 'bearer';
		$settings['expires_at'] = ! empty( $token['expires_in'] ) ? ( time() + absint( $token['expires_in'] ) ) : 0;
		utfeed_save_token_store(
			array(
				'access_token' => $settings['access_token'],
				'refresh_token' => $settings['refresh_token'],
				'token_type' => $settings['token_type'],
				'expires_at' => $settings['expires_at'],
				'connected_at' => ! empty( $tokens['connected_at'] ) ? $tokens['connected_at'] : time(),
			)
		);
		utfeed_update_settings( $settings );
		return true;
	}

	function utfeed_get_api_token() {
		if ( defined( 'UTFEED_X_BEARER_TOKEN' ) && UTFEED_X_BEARER_TOKEN ) {
			return trim( UTFEED_X_BEARER_TOKEN );
		}

		$tokens = utfeed_get_token_store();
		if ( ! empty( $tokens['access_token'] ) ) {
			if ( ! empty( $tokens['expires_at'] ) && ( time() + 60 ) >= absint( $tokens['expires_at'] ) ) {
				utfeed_refresh_access_token();
				$tokens = utfeed_get_token_store();
			}
			if ( ! empty( $tokens['access_token'] ) ) {
				return $tokens['access_token'];
			}
		}

		$settings = utfeed_get_settings();
		if ( ! empty( $settings['access_token'] ) ) {
			if ( ! empty( $settings['expires_at'] ) && ( time() + 60 ) >= absint( $settings['expires_at'] ) ) {
				utfeed_refresh_access_token();
				$settings = utfeed_get_settings();
			}
			if ( ! empty( $settings['access_token'] ) ) {
				return $settings['access_token'];
			}
		}

		if ( ! empty( $settings['manual_bearer_token'] ) ) {
			return $settings['manual_bearer_token'];
		}

		$env_token = getenv( 'UTFEED_X_BEARER_TOKEN' );
		return $env_token ? trim( $env_token ) : '';
	}

	function utfeed_handle_oauth_disconnect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'ultimate-twitter-feeds' ) );
		}
		check_admin_referer( 'utfeed_oauth_disconnect' );

		$settings = utfeed_get_settings();
		$settings['access_token'] = '';
		$settings['refresh_token'] = '';
		$settings['expires_at'] = 0;
		$settings['token_type'] = '';
		$settings['connected_at'] = 0;
		utfeed_update_settings( $settings );
		utfeed_clear_token_store();

		wp_safe_redirect( utfeed_get_settings_url( 'disconnected' ) );
		exit;
	}
	
	function utfeed_pre($obj){
		echo "<pre>";
		print_r($obj);
		echo "</pre>";
	}
