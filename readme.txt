=== MailPoet Newsletters (New) ===
Contributors: mailpoet, wysija
Tags: newsletter, email, welcome email, post notification, autoresponder, signup, subscription, SMTP
Requires at least: 4.7
Tested up to: 4.9
Requires PHP: 5.6
Stable tag: 3.7.8
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Create and send beautiful emails and newsletters from WordPress.

== Description ==

The new MailPoet is here! With our new free sending plan, send unlimited emails to up to 2,000 subscribers. All without ever leaving WordPress.

= What's inside? =

* New designer with responsive templates
* Send your emails with MailPoet’s Sending Service (optional)
* Improved user experience
* Easy configuration
* Solid reliability
* GDPR compliant
* Weekly releases

= See it in action. =
[Test the demo](http://demo.mailpoet.com/) or [see the 2 min. video](https://vimeo.com/223581490)
[vimeo https://vimeo.com/223581490]

= Before you install =

Take note:

* Not optimized for right-to-left (RTL) languages yet
* Multisite works, but is not officially supported
* Review [our minimum requirements](http://beta.docs.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3)

= What about the Premium? =

MailPoet is fully featured in its free version and works up until you have "2000" subscribers.

The Premium version adds the following features:

* for each newsletter, see which subscribers opened it and which links they clicked
* ability to send Welcome Emails automatically; i.e. "Welcome to my Newsletter” autoresponders or multi-email courses
* removes the small MailPoet logo in the footer of your emails
* same day support (Monday to Friday)
* send to over "2000" subscribers with your own sending method
* see the [short video summary on the Premium](http://beta.docs.mailpoet.com/article/208-video-overview-of-mailpoet-premium)

Plus: if you sign up to one of our sending plans, you’ll get all of these fancy features for free. Visit the Premium page inside the plugin for more info.

= Translations =

* French (FR and CA)
* German
* Italian
* Spanish
* Dutch
* Portuguese (BR and PT)
* British
* Russian
* Japanese
* Persian (IR)
* Polish
* Catalan
* Danish
* Swedish
* Turkish

We welcome experienced translators to translate directly on [our Transifex project](https://www.transifex.com/wysija/mp3/). Please note that any translations submitted via the "Translating WordPress" web site will not work.

= Security =

[Security audits are made by LogicalTrust](https://logicaltrust.net/en/), an independent third party.

Have a question for us? Reach us at security@ our domain.

== Installation ==

There are 3 ways to install this plugin:

= 1. The super easy way =
1. In your WordPress dashboard, navigate to Plugins > Add New
1. Search for `MailPoet`
1. Click on "install now"
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

Stop by our [support site](https://www.mailpoet.com/support).

== Screenshots ==

1. Sample newsletters
2. The drag-and-drop email designer
3. Subscriber management
4. Sending method configuration in Settings
5. Subscriber import (via a CSV file or directly from MailChimp)

== Changelog ==

= 3.7.8 - 2018-06-26 =
* Added: support for long URLs in newsletter links;
* Fixed: controls in editor display correctly;
* Fixed: full post ALC content now displays post images;

= 3.7.7 - 2018-06-20 =
* Changed: MailPoet 3 to no longer work with PHP version 5.3 or older. Please upgrade to PHP 7!
* Added: exit user survey;
* Added: retina display optimized images for MailPoet 3 WordPress plugin entry;
* Fixed: welcome emails are not being sent;
* Fixed: non-Premium users now see a proper call to action for WooCommerce automatic email events;
* Fixed: errors when using Title Only and Display as List setting in ALC content block;
* Fixed: API reports errors when confirmation emails aren't sent. Thanks, Team BrainstormForce;
* Fixed: in some cases, button fonts in newsletter would display in preview incorrectly;
* Fixed: using double quotes cause rendering issues;
* Fixed: MailPoet translation string should not be available on translate.wordpress.org;
* Fixed: word "beta" is duplicated on WooCommerce automatic email select screen;

= 3.7.6 - 2018-06-12 =
* Fixed: Woocommerce email template thumbnail overflowing over content.
* Fixed: Newsletters created before 3.7.4 now follow featured image display rules implemented in latest release;
* Fixed: form subscription success message is now displayed only upon form submission. Thanks, Mariener;
* Fixed: it is now possible to delete smaller content rows;
* Improved: welcome emails to unconfirmed subscribers not to block sending. Thanks, Donald!
* Improved: layout for welcome and update pages.

= 3.7.5 - 2018-06-05 =
* Added: align images left or right of posts excerpts;
* Fixed: post content block image alignment issues.

= 3.7.4 - 2018-05-30 =
* Added: What's New page no longer shows after every update;
* Improved: template selection page thumbnails are larger;
* Improved: updating post notification emails no longer triggers duplicate emails;
* Improved: translation enhancements.

= 3.7.3 - 2018-05-22 =
* Improved: updated dependency libraries to latest versions;
* Improved: performance of scheduling new welcome emails on sites with many of new subscribers. Thanks Donald;
* Fixed: subscriber import tool no longer complains about filenames with multiple periods;
* Fixed: scheduled send tasks are properly rescheduled when updating their parent newsletter's options;
* Fixed: paused post notification emails to not block sending of other emails;
* Fixed: newsletter subject line with shortcodes does not break sending when using our sending service. Thanks, James;
* Fixed: subscription forms to return less information about the subscriber.

= 3.7.2 - 2018-05-15 =
* Added: list of emails a subscriber viewed to GDPR data export;
* Added: list of links a subscriber clicked to GDPR data export;
* Added: a tool to forget subscriber's information for GDPR related data erasure;
* Added: Privacy policy which can be used by WordPress's privacy tool in compliance with GDPR;
* Improved: performance for sites using many post notification emails. Thanks Jose!
* Fixed: Javascript warnings on segments page are removed.
* Fixed: custom field values longer than 255 characters can be stored. Thanks Scott!

= 3.7.1 - 2018-05-08 =
* Added: export of subscriber information (email, personal data and subscription lists) to WordPress 4.9.6 and newer versions in compliance with GDPR requirements;
* Added: notice for those who use legacy PHP versions (<5.6) - MailPoet recommends upgrading to PHP 7.0 or newer!
* Improved: sending resource usage has been optimized for large sites. Thanks, Jose;
* Improved: it is now easier to navigate away from the welcome/changelog page;
* Fixed: functionality to pause and resume sending is restored;
* Fixed: proper sent count is now displayed for welcome notifications. Merci Sébastien!

= 3.7.0 - 2018-04-25 =
* Fixed: subscriber search functionality fixed.

= 3.6.7 - 2018-04-24 =
* Fixed: duplicates in the database will not stop scheduled newsletters anymore.

= 3.6.6 - 2018-04-17 =
* Fixed: missing database records no longer break the sending process. Thanks, Catalin;

= 3.6.5 - 2018-04-10 =
* Premium: subscriber export tool now supports dynamic segments;
* Improved: sending was optimized for large newsletters and slow hosts. Thanks, Alison;
* Fixed: help icon functionality was restored for all users.

= 3.6.4 - 2018-04-03 =
* Fixed: editing sent emails will not remove them from email archive. Thanks David!

= 3.6.3 - 2018-03-28 =
* Fixed: scheduled emails can now be sent normally again. Thanks Neil;
* Fixed: sending to dynamic segments (Premium feature). Thanks to Jilfar;
* Fixed: changing the background colour of column layouts no longer corrupts their display. Thanks Neil!

= 3.6.2 - 2018-03-21 =
* Fixed: sending is faster and uses less resources on sites with large number of emails. Thanks Donald and Hostek support team!
* Fixed: scheduled newsletter task no longer runs non-stop when "site visitor" option is enabled. Thanks to @amedic, @conorsboyd and @aspasa for reporting the issue on the forum!

= 3.6.1 - 2018-03-20 =
* Fixed: prevents sending from being paused for long time during plugin update. Big thanks to Deborah, Kelley, Ciro and Justin!

= 3.6.0 - 2018-03-20 =
* Improved: previously used widgets settings in the designer are automatically saved to save you time;
* Improved: welcome emails are now sent with our API's subscribeToList method, and not just addSubscriber. Thanks to Sandra and Donald;
* Improved: less server resources are required to send to very large number of subscribers;
* Improved: shortcodes can be used inside URLs when click tracking is enabled. Thanks to Bob;
* Fixed: more reliable screenshots of your email templates;

= 3.5.1 - 2018-03-13 =
* Improved: email validation for WordPress user synchronization;
* Fixed: import no longer discards e-mails with dashes. A big thank-you to everyone who reported the issue;
* Fixed: sending does not get stuck on the last step of the newsletter creation process. Thanks, Rene!

= 3.5.0 - 2018-03-06 =
* Premium: bulk actions can now be executed on subscribers belonging to a selected segment;
* Improved: a proper error page is displayed if user credentials can't be verified when clicking a tracked newsletter link. Thanks, Bernhard;
* Fixed: MailPoet polyfills missing mbstring function for WordPress core. Thanks Dioni!

= 3.4.4 - 2018-02-27 =
* Premium: send emails to WooCommerce customers who purchased a specific product or in a specific product category;
* Improved: the template import form is now in its own tab;
* Fixed: subscriber-to-list mappings are now migrated correctly on some installations; Thanks Kevin!
* Fixed: newsletter editor ignores taxonomies without labels when searching for categories or tags. Thanks Jose!

= 3.4.3 - 2018-02-20 =
* Improved: export includes IP address column and differentiates between global and list subscription status;
* Improved: email designer checks if "Automatic Latest Content" widget is present in Post Notification emails.

= 3.4.2 - 2018-02-13 =
* Premium: you can now segment your subscribers by opened/clicked/unopened events;
* Improved: default post search parameters inside newsletter editor can be manually changed using a custom WordPress filter. Thanks Jose Salazar;
* Fixed: saving email templates when website uses both HTTP and HTTPS protocols.

= 3.4.1 - 2018-02-06 =
* Fixed: previously saved templates are now under "Your saved templates";
* Improved: imported templates with no matching category are now added to "Your saved templates".

= 3.4.0 - 2018-01-30 =
* Added: choices of templates are now categorized for clarity;
* Fixed: plugin activation to be able to create all plugin tables with MySQL strict mode enabled. Thank you @deltafactory!
* Fixed: importing subscribers with custom fields. Thanks again @deltafactory!

= 3.3.6 - 2018-01-23 =
* Added: optional reCAPTCHA to protect subscription forms from fake signups.

= 3.3.5 - 2018-01-16 =
* Added: additional tools for our support team to mitigate sending issues;

= 3.3.4 - 2018-01-09 =
* Fixed: the plugin no longer spams the same post notification email to subscribers. Thank you Mark, Bruno, Peter, Aaron, PJ, Silowe, Eytan, Beverly and others for your help!
* Fixed: public assets are loaded for shortcode/PHP form placement methods. Thanks Ehsan!

= 3.3.3 - 2018-01-02 =
* Improved: Welcome emails are now sent for subscribers manually created by administrators;
* Improved: content deletion in email designer to more clearly warn about what is being deleted;
* Improved: HelpScout beacon no longer obstructs pagination in listings.

= 3.3.2 - 2017-12-19 =
* Thanked: 2017 finally saw the release of MailPoet 3 and MailPoet Sending Service. We wouldn't have done it without your patience and support, for which we are extremely grateful. Thank YOU!
* Wished: 2018 turns out to be an amazing year for all of our beloved and new users, and brings new features to our plugin - we've planned some great things and can't wait to implement them. Happy Holidays!
* Improved: MailPoet will not load its public assets when subscription form widgets are not used. Thanks Oliver!

= 3.3.1 - 2017-12-14 =
* Fixed: newsletter open/click rates are properly displayed in listings. Thanks to all who have reported the issue!

= 3.3.0 - 2017-12-12 =
* Premium: you can now segment your subscribers by email opens or clicks;
* Fixed: default newsletter templates will not be duplicated when user switches profile language to one that's different from the system's.

= 3.2.4 - 2017-12-05 =
* Improved: [mailpoet_manage_subscription] always renders for logged in WP users. Thx Jon, Sean, Steve & metaglyphics;
* Fixed: migration from MailPoet 2 on hosts with missing "mbstring" PHP extension. Thanks Alvaro!
* Fixed: updated existing libraries that previously contained security issues. Thanks Rhiannon (@goija) and Bits of Freedom!

= 3.2.3 - 2017-11-29 =
* Fixed: bug that prevented configuring third-party sending methods.

= 3.2.2 - 2017-11-28 =
* Fixed: plugin language changes according to user's profile language;
* Fixed: linked images with spaces inside URL are now properly displayed in Gmail. Thanks, Willie!
* Fixed: proper error message is displayed when sending fails using Amazon SES. Thanks Andres!
* Fixed: error message thrown in a rare case when trying to send a test email.

= 3.2.1 - 2017-11-21 =
* Fixed: Safari bug asking subscribers to leave the first field empty in MailPoet subscription forms;
* Fixed: JavaScript error is not thrown when test email can't be sent;

= 3.2.0 - 2017-11-14 =
* Added: API method to access subscriber data by email;
* Added: API method to unsubscribe from lists;
* Fixed: shortcodes are properly removed from all post excerpts that are included in emails. Thanks Gerhard!
* Premium: you can now view subscribers in dynamic segments.

= 3.1.0 - 2017-11-07 =
* Added: a method to create a new list via our public API;
* Fixed: javascript files are loaded with a dependency on jquery. Thanks George!
* Fixed: WP users sync no longer chokes on NULL values for first/last names. Thanks @cartpauj!
* Fixed: superadmin users on Multisite installations can always access MailPoet on subsites. Thanks Ryan!

= 3.0.9 - 2017-10-31 =
* Improved: search forms in listings ignore preceding and trailing whitespace;
* Fixed: tags aren't shown within categories for automated latest content posts anymore. Thanks Gregor!
* Fixed: our subscription form no longer conflicts with themes/plugins that use jQuery Serialize Object function. Thanks Albert!

= 3.0.8 - 2017-10-24 =
* Improved: unsubscribe link now works in browser and email previews;
* Improved: relative URLs in images are replaced by absolute URLs. Thanks Xavier!
* Fixed: default email templates to be translated into your language. Thanks Webteam!
* Fixed: subscription form's "required field" validation message is now translatable. Thanks, Frank!
* Fixed: plugin doesn't fail to activate when WP users table contains custom columns. Thanks Brian!

= 3.0.7 - 2017-10-17 =
* Improved: subscribing from the same IP address is progressively throttled. Thanks Suyog Palav, Piyush Kumar and Bits of Freedom!
* Fixed: WordPress users without an email address will not be added as subscribers;
* Fixed: bug asking subscribers to leave the first field empty in MailPoet subscription forms;
* Fixed: plugin no longer fails to activate on sites when certain user roles do not exist. Thanks to all who reported this!

= 3.0.6 - 2017-10-10 =
* Fixed: subscription forms to not throw form validation engine errors;

= 3.0.5 - 2017-10-10 =
* Added: images can now be aligned left, center or right in email designer;

= 3.0.4 - 2017-10-05 =
* Fixed: dividers and spacers' height can be changed on mouse drag again;

= 3.0.3 - 2017-10-03 =
* Fixed: mixed collation error in WordPress user synchronization. Thanks Chris, Till, Robin, Robero, @Seph, @kaiwen and others for the reports!

= 3.0.2 - 2017-10-03 =
* Improved: plugin capabilities can be managed with Members plugin;
* Improved: removes unsightly horizontal scrollbar in some parts of the newsletter editor;
* Improved: email templates to be displayed in order of last modification;
* Fixed: it's not possible to submit a subscription form multiple times with an existing e-mail address anymore. Thanks Suyog Palav and Bits of Freedom!
* Fixed: users subscribed before registering on a site are synchronized during WP users sync. Thanks Nicolas!

= 3.0.1 - 2017-09-26 =
* Added: images can be resized in newsletter editor;
* Fixed: scheduled newsletters that are unscheduled will not be mistakenly sent. Thx Georges in Provence;
* Fixed: plugin does not time out on sites with a large number of lists and subscribers. Thanks Roy;
* Fixed: long email addresses no longer trigger MySQL errors during subscription. Thank you Sameer Bhatt and Bits of Freedom!

= 3.0.0 - 2017-09-20 =
* Official launch of the new MailPoet. :)
* Improved: MailPoet 3 now works with other plugins that use a supported version of Twig templating engine. Thanks @supsysticcom;
* Added: we now offer a free sending plan for "2000" subscribers or less. Thx MailPoet!

= 3.0.0-rc.2.0.3 - 2017-09-12 =
* Added: hook to override default cron URL. Thanks Fred;
* Improved: WordPress user sync optimized for large membership sites; Thanks Nagui and @deltafactory!
* Improved: column blocks are highlighted when hovering over their tools in the newsletter editor. Now column blocks are easier to visualize;
* Improved: color picker in the the email designer got instant color previews and history of recent picks to help you design newsletter elements more rapidly;
* Fixed: Twig does not throw a deprecated notice. Thanks Pascal;
* Fixed: newsletter editor now longer hangs on Internet Explorer. Thanks Danielle;

= 3.0.0-rc.2.0.2 - 2017-09-05 =
* Added: browser spellchecker in newsletter editor;
* Improved: newsletter editor uses an optimized data object to display post types. Thanks Facundo;
* Fixed: when signup confirmation is disabled, subscribers added via API are automatically confirmed;
* Fixed: browser preview from within newsletter editor works on HTTP sites loaded over HTTPS. Thanks @dave2084!

= 3.0.0-rc.2.0.1 - 2017-08-30 =
* Fixed: newsletters with emojis are properly saved and sent on certain hosts. Thanks Alison, Scott and Swann!
* Fixed: plugin activates on multisite environments;
* Fixed: subscription forms with list selection field are working now;
* Fixed: newsletter editor does not require "unsubscribe" link when a third-party sending method is used;

= 3.0.0-rc.2.0.0 - 2017-08-29 =
* Improved: MailPoet updates on high traffic sites now use less resources;
* Improved: newsletter is saved when "next" button is pressed in newsletter editor;
* Improved: allows editors to manage emails and adds hooks to extend plugin's roles/permissions;
* Improved: we collect more informative data from those who share their data with us. You should too!
* Fixed: subscription management form works again;
* Fixed: MailPoet 3 no longer processes the "wysija_form" shortcode used by the old MailPoet 2 to allow both plugins to display forms. Please use the newer "mailpoet_form" shortcode instead. Thx Lynn!
* Fixed: reactivated post notifications will be sent on next scheduled time. Thx Luc!
* Fixed: updating subscription information of WP users no longer erases their first/last name;
* Fixed: automated latest content in welcome emails always displays the latest posts. Kudos Ehi!

= 3.0.0-rc.1.0.4 - 2017-08-22 =
* Added: newsletters can now be paused and edited while sending;
* Added: tooltips across the UI to quickly answer questions we often get on support;
* Added: extra measures to help prevent fake subscriptions by bots;
* Added: a hook to modify maximum post excerpt length;
* Fixed: it is possible again to switch to other sending methods after choosing MailPoet Sending Service. Thx Bastien!

= 3.0.0-rc.1.0.3 - 2017-08-15 =
* Improved: newsletter browser preview window in newsletter editor now fits correctly in any screen height;
* Improved: date shortcode displays WP time and is available to be translated into other laguages. Thanks Rik and Yves!
* Improved: rendered form body can be modified via a hook. Thanks, Vrodo;
* Fixed: subscriber export will not fail on hosts with PHP's set_time_limit() disabled. Thanks, @miguelarroyo;

= 3.0.0-rc.1.0.2 - 2017-08-08 =
* Fixed: correct error notice is displayed when using IIS server. Thanks @flauer!

= 3.0.0-rc.1.0.1 - 2017-08-02 =
* Fixed: we were so excited to come out of Beta, we forgot to include translation files. Woops :)

= 3.0.0-rc.1.0.0 - 2017-08-01 =
* Improved: MailPoet 3 is no longer in Beta!
* Improved: blockquotes in posts are now displayed in emails; Thanks @newslines!
* Improved: a bottom padding is added to every last element of a column, except if it's full width image;
* Fixed: recommended sending limit values are properly updated when the sending method is modified;
* Fixed: welcome newsletter listings page now loads faster; Thanks Luc!
* Fixed: [newsletter:post_title] properly displays titles of custom post types; Thanks Adrian!
* Fixed: post images are displayed in expected positions; Thanks Gary!

= 3.0.0-beta.1 - 2016-10 =

* Initial public beta release.
