=== MailPoet 3 - Beta Version ===
Contributors: mailpoet, wysija
Tags: newsletter, email, welcome email, post notification, autoresponder, mailchimp, signup, smtp
Requires at least: 4.6
Tested up to: 4.7
Stable tag: 3.0.0-beta.10
Create and send beautiful emails and newsletters from WordPress.

== Description ==

Try the new MailPoet! This is a beta version of our completely new email newsletter plugin.

= What's new? =

* New email designer
* Responsive templates
* Send with MailPoet's sending service
* Fast user interface
* Easier initial configuration

[Try the demo.](http://demo3.mailpoet.com/launch/)

= Check out this 2 minute video. =

[vimeo https://vimeo.com/183339372]

= Use at your own risk! =

Use [the current stable MailPoet](https://wordpress.org/plugins/wysija-newsletters/) instead of this version if you are not a power user.

* This beta version is for testing purposes only!
* Not RTL compatible
* We expect bug reports from you!
* Multisite not supported
* No migration script from MailPoet 2.X to this version
* Weekly releases

= Premium version =

Not available yet. Limited stats in free version.

= Translations in your language =

We accept translations in the repository.

== Installation ==

There are 3 ways to install this plugin:

= 1. The super easy way =
1. In your Admin, go to menu Plugins > Add
1. Search for `mailpoet`
1. Click to install
1. Activate the plugin
1. A new menu `mailpoet` will appear in your Admin

= 2. The easy way =
1. Download the plugin (.zip file) on the right column of this page
1. In your Admin, go to menu Plugins > Add
1. Select the tab "Upload"
1. Upload the .zip file you just downloaded
1. Activate the plugin
1. A new menu `MailPoet` will appear in your Admin

= 3. The old and reliable way (FTP) =
1. Upload `mailpoet` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. A new menu `MailPoet` will appear in your Admin

== Frequently Asked Questions ==

= Need help? =

Our [support site](https://docs.mailpoet.com/) has plenty of articles. You can write to us on the forums too.

== Screenshots ==

1. Sample newsletters.
2. The drag & drop editor.
3. Subscriber management.
4. Sending method configuration in Settings.
5. Importing subscribers with a CSV or from MailChimp.

== Changelog ==

= 3.0.0-beta.10 - 2016-12-27 =
* Improved: newsletter is saved prior to sending an email preview;
* Improved: subscription management page conditionally displays the "bounced" status;
* Improved: deleted lists are displayed in newsletter listings;
* Fixed: newsletter/subscriber/list/form dates are properly formatted according to WP settings;
* Fixed: emails' "Return-path" header is set to the bounce address configured in Settings->Advanced;
* Fixed: archived newsletters' shortcode works for site visitors;
* Fixed: unicode support for newsletters.

= 3.0.0-beta.9 - 2016-12-20 =
* Improved: the plugin is now tested up to WP 4.7;
* Improved: MailPoet's sending service bounce status API update;
* Improved: change duplicate subscribers import message to be more descriptive;
* Fixed: database character set and time zone setup;
* Fixed: alignment of post titles inside notificaiton emails;
* Fixed: partially generated or missing translations from .pot file.

= 3.0.0-beta.8 - 2016-12-13 =
* Added: MailPoet's sending service can now sync hard bounced addresses with the plugin to keep your lists tidy and clean;
* Improved: gracefully catch vendor library conflicts with other plugins. Thx Vikas;
* Improved: force browsers to load the intended JS and CSS assets by adding a parameter, ie style.css?ver=x.y.z;
* Fixed: render non paragraph elements inside a block quote. Thx Remco!;
* Fixed a query that's gone awry in Mysql version 5.6. Dank je Pim!

= 3.0.0-beta.7.1 - 2016-12-06 =
* Improved: allow user to restart sending after sending method failure;
* Fixed: subscribers are not added to lists after import;
* Fixed: sending should stop when newsletter is trashed;
* Fixed: update database schema after an update which fixes an SQL error;
* Fixed: status of sent newsletters is showing "paused" instead of "sent";
* Fixed: dividers in Automatic Latest Posts posts are not displayed. Thx Gregor!;
* Fixed: shortcodes (ie, first name) are not rendered when sending a preview;
* Fixed: count of confirmed subscribers only in step 2 of import is erroneous.

= 3.0.0-beta.6 - 2016-11-29 =
* Added: "bounced" status has been added to subscribers;
* Improved: execution time enforced between individual send operations. Avoids duplicate sending on really slow servers;
* Improved: Welcome emails are given higher priority for sending;
* Fixed: Welcome emails are not scheduled for WP users;
* Fixed: Unicode characters in FROM/REPLY-TO/TO fields are not rendered;
* Fixed: sending HTML emails with Amazon SES works again. Kudos Alex for reporting;
* Fixed: import fails when subscriber already exists in the database but the email is in different case format. Thx Ellen for telling us;
* Fixed: ampersand char ("&") inside the subject line won't throw errors in browser preview. Thanks Michel for reporting.

= 3.0.0-beta.5 - 2016-11 =
* Fixed ALC block in newsletter editor to not show tools of content blocks;
* Fixed Sending Queue to remove post notification history newsletter when sending queue record is removed;
* Fixed vendor library initialization path on certain configurations;
* Optimized image assets to reduce file size;
* Added security fixes;
* Added plugin requirements checker;
* Fixed "MailPoet Page" custom post type to not display an entry on admin menu;
* Fixed language strings in subscriber import;
* Added "Get back to MailPoet" button on plugin update page.

= 3.0.0-beta.4 - 2016-11 =

* Updated HelpScout beacon to provide support articles;
* Fixed handling of URLs containing shortcodes in newsletter editor;
* Security fixes;
* Fixed subscriber count to not count trashed subscribers;
* Fixed template renderer to gracefully display an error when template caching issues arise;
* Added security measures to prevent mass subscriptions.

= 3.0.0-beta.3 - 2016-11 =

* Improved compatibility with PHP 7;
* Fixed showing current newsletter status in newsletter listings when there are no subscribers to send to;
* Removed obsolete libraries;
* Fixed security issues;
* Fixed html form embed code to use correct paths;
* Updated settings documentation URL;
* Improved text fitting in newsletter type/template selection boxes;
* Fixed Populator compatibility with earlier PHP versions;
* Fixed newsletter number shortcode for notification newsletters;
* Enhanced HelpScout support beacon report with extra support data;
* Fixed email renderer to not throw entity warnings on earlier PHP versions;
* Fixed newsletter preview incompatibility errors for earlier PHP versions.

= 3.0.0-beta.2 - 2016-10 =

* Fixed compatibility issues with PHP versions earlier than PHP 5.6;
* Renamed 'Emails' email type to 'Newsletters'.

= 3.0.0-beta.1 - 2016-10 =

* Initial public beta release.
