<?php
class utfeed_widget extends UTFEED_Widgets
{

	function __construct()
	{
		parent::__construct('utfeed_widget', __(UTFEED_PLUGIN_TITLE_WIDGET, 'ultimate-twitter-feeds'), ['description' => __(UTFEED_PLUGIN_TITLE_WIDGET, 'ultimate-twitter-feeds')]);
	}

	public function widget($args, $instance)
	{
		$instance = $this->GetInstanceValues($instance);
		echo $args['before_widget'];
		echo $this->GetWidgetTitle($args, $instance);
		echo $this->GetWidgetHtml($instance);
		echo $args['after_widget'];
	}

	public function form($instance)
	{
		$values = $this->GetInstanceValues($instance);
		extract($values);
?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:', 'ultimate-twitter-feeds'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('feed_type')); ?>"><?php _e('Feed Type:', 'ultimate-twitter-feeds'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('feed_type')); ?>" name="<?php echo esc_attr($this->get_field_name('feed_type')); ?>">
				<?php
				$feed_types = UTFEED_Twitter::getTwitterFeedTypes();
				foreach ($feed_types as $option) {
					echo '<option value="' . esc_attr($option[0]) . '" ' . selected($feed_type, $option[0], false) . '>' . esc_attr($option[1]) . '</option>';
				}
				?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('handle')); ?>"><?php _e('Handle:', 'ultimate-twitter-feeds'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('handle')); ?>" name="<?php echo esc_attr($this->get_field_name('handle')); ?>" type="text" value="<?php echo esc_attr($handle); ?>" />
		</p>
		<p>
			<input type="hidden" id="<?php echo esc_attr($this->get_field_id('actual_handle')); ?>" name="<?php echo esc_attr($this->get_field_name('actual_handle')); ?>" value="<?php echo esc_attr($actual_handle); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('feed_width')); ?>">
				<?php _e('Width:', 'ultimate-twitter-feeds'); ?>
			</label>
			<input id="<?php echo esc_attr($this->get_field_id('feed_width')); ?>" name="<?php echo esc_attr($this->get_field_name('feed_width')); ?>" type="number" value="<?php echo esc_attr($feed_width); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('feed_height')); ?>">
				<?php _e('Height:', 'ultimate-twitter-feeds'); ?>
			</label>
			<input id="<?php echo esc_attr($this->get_field_id('feed_height')); ?>" name="<?php echo esc_attr($this->get_field_name('feed_height')); ?>" type="number" value="<?php echo esc_attr($feed_height); ?>" />
			<font size="2px"><?php _e('Height will not work for Single Tweet!', 'ultimate-twitter-feeds'); ?></font>
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('feed_lang')); ?>">
				<?php _e('Language:', 'ultimate-twitter-feeds'); ?>
			</label>
			<select class="widefat" name="<?php echo esc_attr($this->get_field_name('feed_lang')); ?>" id="<?php echo esc_attr($this->get_field_id('feed_lang')); ?>">
				<?php
				$langs = UTFEED_Twitter::getTwitterFeedLangs();
				foreach ($langs as $lang) {
					echo '<option value="' . esc_attr($lang[0]) . '" ' . selected($feed_lang, $lang[0], false) . '>' . esc_attr($lang[1]) . '</option>';
				}
				?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('feed_theme')); ?>">
				<?php _e('Theme:', 'ultimate-twitter-feeds'); ?>
			</label>
			<select name="<?php echo esc_attr($this->get_field_name('feed_theme')); ?>" id="<?php echo esc_attr($this->get_field_id('feed_theme')); ?>">
				<option value="light" <?php selected($feed_theme, "light"); ?>>
					<?php _e('Light', 'ultimate-twitter-feeds'); ?>
				</option>
				<option value="dark" <?php selected($feed_theme, "dark"); ?>>
					<?php _e('Dark', 'ultimate-twitter-feeds'); ?>
				</option>
			</select>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked($values['feed_track'], "on") ?> id="<?php echo esc_attr($this->get_field_id('feed_track')); ?>" name="<?php echo esc_attr($this->get_field_name('feed_track')); ?>" />
			<label for="<?php echo esc_attr($this->get_field_id('feed_track')); ?>" title="<?php _e('Click Here To Read More!', 'ultimate-twitter-feeds') ?>"><?php _e('Opt-out of tailoring X <a href="https://docs.x.com/x-for-websites/javascript-api/guides/set-up-x-for-websites" target="_blank">?</a>', 'ultimate-twitter-feeds'); ?>
			</label>
		</p>
<?php
	}

	public function update($new_instance, $old_instance)
	{
		return $this->UpdateWidgetInstace($new_instance, $old_instance);
	}
}
