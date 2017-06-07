=== MailPoet 3 - Beta Version ===
Contributors: mailpoet, wysija
Tags: newsletter, email, welcome email, post notification, autoresponder, signup, subscription, SMTP
Requires at least: 4.6
Tested up to: 4.7.5
Stable tag: 3.0.0-beta.34.0.0
Create and send beautiful emails and newsletters from WordPress.

== Description ==

Try the new MailPoet! This is a beta version of our completely new email newsletter plugin. [Or test the demo.](http://demo3.mailpoet.com/launch/)

= What's new? =

* New email designer
* Responsive templates
* Send with MailPoet's Sending Service
* Fast user interface
* Easier initial configuration
* Weekly releases since November 2016

= Check out this 2 minute video. =

[vimeo https://vimeo.com/183339372]

= Use at your own risk! =

Use [the current stable MailPoet](https://wordpress.org/plugins/wysija-newsletters/) instead of this beta version if you are not a power user.

* Report bugs!
* Not RTL optimized, but it works
* Multisite works but is not officially supported
* Migration script from MailPoet 2.X to this version coming soon.

= Premium version =

Not available yet. Limited stats in free version.

= Translations =

* French
* German
* Italian
* Spanish
* Dutch
* Portuguese (BR and PT)
* British
* Russian
* Persian (IR)

We welcome translators to translate directly on [our Transifex project](https://www.transifex.com/wysija/mp3/). Please note that any translations submitted via the "Translating WordPress" web site will not work!

== Installation ==

There are 3 ways to install this plugin:

= 1. The super easy way =
1. In your WordPress dashboard, navigate to Plugins > Add New
1. Search for `MailPoet`
1. Click on "install now" under "MailPoet 3 – Beta Version"
1. Activate the plugin
1. A new `MailPoet` menu will appear in your WordPress dashboard

= 2. The easy way =
1. Download the plugin (.zip file) by using the blue "download" button underneath the plugin banner at the top
1. In your WordPress dashboard, navigate to Plugins > Add New
1. Click on "Upload Plugin"
1. Upload the .zip file
1. Activate the plugin
1. A new `MailPoet` menu will appear in your WordPress dashboard

= 3. The old-fashioned and reliable way (FTP) =
1. Download the plugin (.zip file) by using the blue "download" button underneath the plugin banner at the top
1. Extract the archive and then upload, via FTP, the `mailpoet` folder to the `<WP install folder>/wp-content/plugins/` folder on your host
1. Activate the plugin
1. A new `MailPoet` menu will appear in your WordPress dashboard

== Frequently Asked Questions ==

= Need help? =

Our [support site](https://beta.docs.mailpoet.com) has plenty of articles. You can write to us on the forums too.

== Screenshots ==

1. Sample newsletters
2. The drag-and-drop email designer
3. Subscriber management
4. Sending method configuration in Settings
5. Subscriber import (via a CSV file or directly from MailChimp)

== Changelog ==

= 3.0.0-beta.34.0.0 - 2017-06-07 =
* Added: Premium features are officially available. Free users can visit the Premium page in the menu for more info. Premium users: get your key on account.mailpoet.com to continue using Premium;
* Improved: email addresses are now validated using WordPress is_email() function. Kudos Oskar L. and cnesbit!;
* Fixed: scheduled sending queue jobs are paused when post notifications are made inactive. Thanks Oskar!;
* Fixed: post notification history no longer displays a blank subject for notifications waiting in queue. Thanks Lyon!;
* Fixed: unsubscribe page works again. Thx Oskar one more time!

= 3.0.0-beta.33.1 - 2017-05-30 =
* Updated: minimum required PHP version was increased from 5.3 to 5.3.3. Don't be stuck in the last decade. Ask your host to upgrade you to PHP 7;
* Improved: we now bundle multilingual translations that are 75% or more complete (a decrease from the previous 100% threshold);
* Fixed: âccéntèd characters are properly saved and displayed on all hosts. WARNING: non-English language users are advised to back up their data before upgrading and contact us if something goes wrong;
* Fixed: when subscription confirmation is enabled, welcome notifications will only get scheduled when one's subscription is confirmed;
* Fixed: subscription widget's title is styled in accordance with the active theme's configuration.

= 3.0.0-beta.32 - 2017-05-23 =
* Added: API methods for 3rd party plugins to add subscribers to MailPoet. Which plugins should we connect to?

= 3.0.0-beta.31 - 2017-05-16 =
* Improved: automated latest content/post search boxes in the editor now return up to 50 results;
* Improved: sending progress bar got a new look;
* Improved: added plugin translation to Persian (Iran) language. Thanks Ali Reza Karami!;
* Fixed: submission of subscription forms with list selection or non-text custom fields works again. Thanks Stefan!;
* Fixed: subscription management form works fine again;
* Fixed: invalid license key warnings are temporarily hidden if a key is empty;
* Fixed: newsletter link hashes are much less likely to collide. Thanks Sherrie!

= 3.0.0-beta.30 - 2017-05-09 =
* Fixed: list buttons (ordered/unordered) were added back to the newsletter designer's WYSIWYG editor;
* Fixed: form editor properly displays custom field names when notifying of a completed action (add/update/delete).

= 3.0.0-beta.29 - 2017-05-02 =
* Improved: added a filter allowing to change SMTP configuration. Thanks Luc!
* Improved: MailPoet now avoids conflicts with other plugins using footer scripts. Thanks Mike and Tina!
* Improved: newsletter stats got a new look with badges that help to measure success of a campaign at a glance;
* Fixed: restoring trashed newsletters restores their sending progress;
* Fixed: trashed lists no longer appear as filters in listings. Thanks Luc and Marc!
* Fixed: newsletter subscription management, unsubscription, browser preview links now work with tracking enabled. Thanks Luc!
* Fixed: shortcode's default values are used when subscriber does not have first or last names;

= 3.0.0-beta.28 - 2017-04-25 =
* Improved: now you can subscribe to our brand new email course on the Welcome page;
* Improved: API is now versioned. More goodies to come for 3rd-party developers!;
* Improved: List-Unsubscribe header is added to newsletters. Thanks Galfom!;
* Fixed: editor loads correct newsletters when the specified ID is greater or equals 1000. Thx Jim!;
* Fixed: created/updated subscriber count in import results is shown correctly for large imports;
* Fixed: some admin notices now look better.

= 3.0.0-beta.27 - 2017-04-18 =
* Improved: a warning notice is displayed when the required XML and ZIP PHP extensions are missing;
* Improved: when clicking on a text block inside the email designer, the text cursor is positioned where the click took place;
* Fixed: images remotely added inside the email designer are no longer scaled down to 281px. Thanks Marcelo;
* Fixed: re-importing existing users no longer resets their subscription status. Thanks Marco;
* Fixed: import doesn't fail on certain MySQL setups when subscribers' first and/or last name data is missing;
* Fixed: custom field data no longer get swapped between subscribers during import. Thanks Eric;
* Fixed: automatic latest content block now properly applies tag/category filters to all post types. Thanks JP;
* Fixed: various minor issues.

= 3.0.0-beta.26 - 2017-04-11 =
* Fixed: interactive widget customizer is now working with MailPoet form widgets. Thanks Peter and Charis!
* Fixed: multi-line headings are now properly displayed in emails. Thanks Karen!

= 3.0.0-beta.25 - 2017-04-04 =
* Improved: subscriber listings with large number of subscribers (tens of thousands) now load much faster on MySQL 5.5 and lower. Thanks Moulouk!;
* Fixed: updating sending frequency no longer breaks limit enforcement. Thx Vincent!;
* Fixed: sending works again on hosts running (very old) PHP version 5.3. WordPress recommends PHP 7 or newer. Ask your host how to upgrade. Thanks Emmanuel.

= 3.0.0-beta.24 - 2017-03-28 =
* Improved: clarified UI language in Settings and Import. Thanks Lloyd, @rtomo and @perthmetro;
* Added: hooks and filters for premium features. Thx Alex;
* Premium: Google Analytics tracking is now enabled. Get in touch with us if you're a premium user!
* Fixed: multilingual translations are no longer breaking the UI. Thanks Marco;
* Fixed: tracking image inside newsletters is now transparent and does not produce a false positive result during VaultPress's security scan. Thanks Raw-B.

= 3.0.0-beta.23.2 - 2017-03-14 =
* Improved: added plugin translations to Dutch, English (UK), French, German, Italian, Portuguese (Brazil), Portuguese (Portugal), Russian and Spanish languages. Thank you translators!
* Fixed: unsubscribed subscriber will no longer receive newsletters (whoops!). Thanks, Oskar;
* Fixed: previously scheduled send tasks are rescheduled when post notifications' scheduling options change. Thanks, Karen and Eric!
* Fixed: Amazon SES sending method now works regardless of custom "arg_separator" set in PHP's configuration. Thanks Lukas!

= 3.0.0-beta.22 - 2017-03-07 =
* Improved: sending very large emails with our sending service is less likely to time out. Thanks Mark!;
* Fixed: WordPress warnings are no longer displayed in the editor's contents;
* Small, little and tiny improvements to show we pay attention to details;
* British scientists say people whose sites run on PHP below version 7 have the lowest quality of life.

= 3.0.0-beta.21 - 2017-02-28 =
* Fixed: newsletter sending process will fully stop when sending is paused. Thanks Terry!
* Fixed: MailPoet sending method will work on sites using PHP 5.3. Thanks Jeff!
* Fixed: bulk trashing and restoring newsletters and forms will work on sites using PHP 5.3. Thanks Scott!
* We recommend all of you to upgrade to PHP 7. It's faster, more reliable, and safer. It's just a question of asking your host to switch.

= 3.0.0-beta.20 - 2017-02-23 =
* Fixed: scheduling options are properly saved when creating a new or re-saving an existing email. Thx Oskar!;
* Fixed: sending is not interrupted when a post included in the email is trashed. Thanks Bernhard!

= 3.0.0-beta.19 - 2017-02-21 =
* Improved: import uses stricter email validation rules. Kudos Oskar;
* Improved: database is cleaned up after deleting subscribers. Thx Eric;
* Improved: cursor focuses on the modal window and sidebars. A big thanks to users who reported the issue;
* Improved: plugin is leaner;
* Improved: we're translation-ready. Contact us to help and get freebies;
* Improved: detailed error messages are displayed when sending with SMTP fails. Thx Rik;
* Fixed: going back to email designer does not corrupt its contents. Thx Taut;
* Fixed: emails trashed while sending can be resent if restored. Thnx Vlad;
* Fixed: sending to large lists (30,000+) works again. Oskar, again!;
* Fixed: default email templates are not duplicated when changed;
* Fixed: email rendering does not display warning notices on PHP 7.1. Thx Alex;
* Fixed: bulk trashing of subscribers works on PHP 5.3. Thx Chris!

= 3.0.0-beta.18 - 2017-02-14 =
* Fixed: subscriber stats for lists are accurately calculated;
* Fixed: 'Create a new form' link in the MailPoet Form widget now leads to the Form editor;
* Fixed: category names are shown for Automated latest content widget posts on WP 4.7. Thanks Christopher!;
* Fixed: SendGrid error messages are properly displayed. Thanks Larry!;
* Fixed: non-Latin-1 characters are now rendered on some hosts running PHP 5.3. Thanks Andreas!

= 3.0.0-beta.17 - 2017-02-01 =
* Added: send in style with MailPoet's own sending service. Visit your MailPoet Settings > Send with... tab.

= 3.0.0-beta.16 - 2017-01-31 =
* Improved: Updated language strings for better translation support;
* Fixed: subscription forms now allow to subscribe only to specified lists. Thanks Paul!
* Fixed: subscription forms now ignore any extra fields added not via the Form editor. Thx again Paul!
* Fixed: previewing sent welcome emails now displays latest email version. Thanks Tim!
* Fixed: plugin no longer triggers a PHP error during initialization on hosts using PHP 5.3;
* Fixed: plugin warns about missing required PDO_MYSQL extension.

= 3.0.0-beta.15 - 2017-01-24 =
* Fixed: plugin no longer throws a fatal exception error on (prehistoric :)) hosts running older versions than PHP 5.3. Thanks Otto & jtm12!;
* Fixed: users who are not subscribed to any list can be filtered in the admin panel;
* Fixed: newsletter preview links can now be shared with non WP users.

= 3.0.0-beta.14 - 2017-01-19 =
* Fixed: images can't be added to newsletters. Thanks Leon!;
* Fixed: forms require first & last name input fields on some systems;
* Fixed: unable to remove subscribers from lists in admin panel. Thanks Kay!

= 3.0.0-beta.13 - 2017-01-17 =
* Improved: style/script conflicts on MailPoet pages are now resolved by unloading non-default assets. Thx Michel for reporting one such case!;
* Fixed: MySQL wait_timeout of less than 20 seconds results in errors when sending. Thx Tim!;
* Fixed: unsubscribe URL doesn't work when BuddyPress is enabled;
* Fixed: some form styles aren't saved. Thanks Pete!;
* Fixed: typo in subscription management shortcode instructions. Thx Tim once more!

= 3.0.0-beta.12 - 2017-01-10 =
* Improved: faster load times of Emails page with large database;
* Improved: sender header is now set for SMTP/PHPMail method to work with MS Exchange. Thx Karsten!;
* Improved: better asset conflict management with other plugins;
* Fixed: newly published custom post types are now sent. Thx Jim!;
* Fixed: post notifications now sent when ALC block is configured to display titles only. Thx Pete;
* Fixed: shortcode "date:dtext" displays full name (e.g., Sunday) instead of abbreviated (e.g., Sun);
* Fixed: hide mailer error on send previews. Thx Karsten again!;
* Fixed: various minor issues.

= 3.0.0-beta.11 - 2016-12-31 =
* Improved: newsletters' statistics are generated in a split second;
* Fixed: subscribers' data is properly saved on repeat and/or multiple subscription attempts;
* Fixed: WP posts are displayed/rendered with proper line breaks and spaces;
* Fixed: preview-by-email works once again;
* Wished: 2017 sees the release of the fantastic MailPoet 3 and the super-duper MailPoet Sending Service;
* Wished: 2017 turns out to be an amazing year for all of our beloved and new users!

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
