=== MailPoet Newsletters (New) ===
Contributors: mailpoet, wysija
Tags: newsletter, email, welcome email, post notification, autoresponder, signup, subscription, SMTP
Requires at least: 4.7
Tested up to: 4.9
Requires PHP: 5.3
Stable tag: 3.7.3
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
* Weekly releases

= See it in action. =
[Test the demo](http://demo3.mailpoet.com/launch/) or [see the 2 min. video](https://vimeo.com/223581490)
[vimeo https://vimeo.com/223581490]

= Before you install =

Take note:

* Not optimized for right-to-left (RTL) languages yet
* Multisite works, but is not officially supported
* Please check the translations in your language
* Review [our minimum requirements](http://beta.docs.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3)

= What about the Premium? =

MailPoet is fully featured in its free version and works up until you have 2000 subscribers.

The Premium version adds the following features:

* for each newsletter, see which subscribers opened it and which links they clicked
* ability to send Welcome Emails automatically; i.e. "Welcome to my Newsletter” autoresponders or multi-email courses
* removes the small MailPoet logo in the footer of your emails
* same day support (Monday to Friday)
* send to over 2000 subscribers with your own sending method
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
* Added: we now offer a free sending plan for 2000 subscribers or less. Thx MailPoet!

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

= 3.0.0-beta.37.0.0 - 2017-07-25 =
* Improved: we collect more informative data from those who share it with us in order to improve the plugin. You should share too!
* Fixed: deleted WordPress users are removed from the WordPress Users list as well;
* Fixed: shortcodes for custom fields are now inserted correctly in the email designer;
* Fixed: MailPoet Sending Service stays activated after saving Settings;
* Fixed: improperly rendered newsletters will not be sent. Thanks Scott and Alison!

= 3.0.0-beta.36.3.1 - 2017-07-18 =
* Added: you can now duplicate any item in the email designer;
* Improved: added filter to specify custom SMTP connection timeout value. Thanks, Rik;
* Improved: added a custom filter to whitelist JS/CSS styles that are loaded by other plugins on MailPoet's pages;
* Fixed: sending is not interrupted if a newsletter contains URLs with unicode characters. Thanks Sam!
* Fixed: sent date is reset when newsletter is duplicated;
* Fixed: SMTP sending frequency is properly updated when changed;
* Fixed: newsletter/form/subscriber listings no longer throw an error on some PHP 5.3 hosts. Thanks, Jérôme!

= 3.0.0-beta.36.3.0 - 2017-07-11 =
* Added: migration script for MailPoet 2 users now imports settings;
* Fixed: emails are sorted by date sent instead of modified date; thanks Scott
* Fixed: cursor doesn't get stuck on "move" icon when editing text;
* Fixed: repeated subscriptions don't duplicate welcome notifications; thanks Luc

= 3.0.0-beta.36.2.0 - 2017-07-04 =
* Added: 13 new default templates to choose from;
* Added: a new help page in the menu to help us help you better;
* Added: link to list of form plugins that work with MailPoet in our Forms page;
* Improved: display infinite number of posts in Posts widget, instead of just 10. Kudos Keith!
* Fixed: DB connection exceptions are now safely handled. Thanks FxB!

= 3.0.0-beta.36.1.0 - 2017-06-27 =
* Improved: error notices are displayed when AJAX requests fail;
* Added: MailPoet 2 forms are migrated when MailPoet 3 is installed/reinstalled.

= 3.0.0-beta.36.0.1 - 2017-06-23 =
* Improved: preheader will now be hidden in Gmail app;
* Fixed: subscription forms now work without causing "missing file" errors. Thanks Sherrie!
* Fixed: Premium keys status to not be invalidated after saving Settings;
* Fixed: email shortcodes are correctly displayed in Newsletter Archive. Thanks Lukáš!

= 3.0.0-beta.36.0.0 - 2017-06-20 =
* Improved: "view in browser" link is disabled in preview emails. Thanks Riccardo;
* Improved: show a warning when activating on Multisite environments;
* Improved: suggest to activate MailPoet Sending Service after a successful key check;
* Added: MailPoet Sending Service sets the List-Unsubscribe header;
* Fixed: outdated JS assets aren't loaded in new releases;
* Fixed: settings page is not blocked any more if you have more than 2000 subscribers which prevented Premium version updates;
* Fixed: premium and Welcome pages are correctly formated in WP 4.8;
* Fixed: scheduled regular emails are now sent. Thanks Karen;
* Fixed: subscription form no longer throws an error message when included in a popup. Thanks Gregor.

= 3.0.0-beta.35.0.0 - 2017-06-13 =
* Added: Subscriber and List migration from MailPoet 2, the option will be offered for new installations;
* Improved: switched "Your own website" sending method to use PHPMailer library from WordPress;
* Fixed: "Subscriber Import" screen allows hyphens in email addresses. Thanks Cherian!
* Fixed: "addSubscriber" method in MailPoet API sends confirmation emails;
* Fixed: subscribing via a IPv6 IP address no longer throws an error. Thanks Hans!
* Fixed: "Apply to all" button will apply button styles to Automatic Latest Content as well.

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
