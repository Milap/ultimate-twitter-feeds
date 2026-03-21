=== Ultimate Twitter Feeds ===
Contributors: Milap, Imneerav
Tags: Twitter, Twitter feed, Tweets, Twitter widget
Requires at least: 3.4
Tested up to: 6.9
Stable tag: 0.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.me/MilapPatel

Display lightweight Twitter feeds. Fetch profiles, lists, or single tweets with customizable sizes and language support.

== Description ==

Ultimate Twitter Feeds is a lightweight plugin to display Twitter feeds. Fetch profiles, lists, or single tweets with customizable size, language.

An inside look:

https://www.youtube.com/watch?v=8gxE5CPLiJM

= Why should you choose Ultimate Twitter Feeds among the many other plugins? =

* Light weight & easy to configure.
* Configuration options like show Tweets from Twitter User Profile, User List and Single Tweet.
* Supports Light and Dark theme.
* Additional options like Width, Height and Language.
* Shortcode support (In Next Release)
* Fast & helpful support.

= Recommended Plugins =

The following plugins are recommended for users:

* [Facebook Page Feeds Widget](https://wordpress.org/plugins/facebook-pagelike-widget/) by Milap – With Facebook Page Feeds Widget, you can display your Facebook Page feeds on your website quickly.

= Privacy Notices =

With the default configuration, this plugin, in itself, does not:

* use cookies.
* track users by stealth.
* write any user personal data to the database.
* send any data to external servers.

== Frequently Asked Questions ==

Do you have questions or issues with Ultimate Twitter Feeds?

1. [Support forum](https://wordpress.org/support/plugin/ultimate-twitter-feeds/)

== Installation ==

1. Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page. Go to widgets page and add \"Ultimate Twitter Feed Widget\" into sidebar and/or footer.

For more details,

https://codex.wordpress.org/Managing_Plugins

== X API Setup (Client ID / Secret) ==

1. Sign in to X Developer Portal: https://developer.x.com/
2. Create a Project and App (or open your existing App).
3. Enable OAuth 2.0 for the App.
4. Add this redirect URI in your X App settings:
   `https://YOUR_SITE/wp-admin/admin-post.php?action=utfeed_x_oauth_callback`
5. Copy your App Client ID and Client Secret.
6. In WordPress admin go to `Settings -> Ultimate Twitter Feeds`, paste Client ID/Secret, and click `Save Settings`.
7. Click `Connect with X` and approve access.

Recommended scopes: `tweet.read users.read offline.access`

== Screenshots ==
1. Widget settings to display Feeds from Twitter User Profile 
2. Widget settings to display Feeds from Twitter User List 
3. Widget settings to display Single User Tweet
4. Displays User Tweets with Light Theme
5. Displays User Tweets with Dark Theme
6. Displays User List Tweets with Light Theme
7. Displays User List Tweets with Dark Theme
8. Displays Single User Tweet with Light Theme
9. Displays Single User Tweet with Dark Theme

== Changelog ==

= 1.0 =
* Initial release.

== Upgrade Notice ==
* Not Applicable