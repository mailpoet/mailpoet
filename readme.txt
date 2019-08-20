=== MailPoet - emails and newsletters in WordPress ===
Contributors: mailpoet, wysija
Tags: email marketing, newsletter, newsletter subscribers, email, welcome email, post notification, WooCommerce emails, newsletter builder, email automation
Requires at least: 4.7
Tested up to: 5.2
Stable tag: 3.35.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Send beautiful newsletters from WordPress. Collect subscribers with signup forms, automate your emails for WooCommerce, blog post notifications & more

== Description ==

With MailPoet, your website visitors can sign up as newsletter subscribers and build your mailing list, all without leaving your WordPress admin.

Our newsletter builder integrates perfectly with WordPress so any website owner can create beautiful emails from scratch or by using our responsive templates that display flawlessly across all devices.

Schedule your newsletters, send them right away or set it up to send new blog post notifications automatically in just a few clicks.

Trusted by 300,000 WordPress websites since 2011.

**New!** Our Premium is now free for sites with 1,000 subscribers or fewer.

[Visit our website to see the templates or try the demo](https://www.mailpoet.com/)

= All features =

* Create and add a newsletter subscription form to your website
* Manage your subscribers and subscriber lists in WordPress
* Build and send newsletters with WordPress
* Create automatic emails to send new post notifications
* Send automated signup welcome emails (**now in free**)
* Increase your sales with our emails for WooCommerce (Premium)
* Insightful stats on your audience engagement (Premium)

= Why choose MailPoet =

* Easy to use WordPress newsletter builder
* Beautiful responsive templates
* No configuration needed: works out of the box
* Small site owners with lists of 1,000 subscribers or fewer get the Premium for free. [Read More](https://www.mailpoet.com/free-plan/)
* GDPR compliant

= See it in action =

[Test the demo](http://demo.mailpoet.com/) or [see the 2 min. video](https://vimeo.com/223581490)
[vimeo https://vimeo.com/223581490]

= Before you install =

Please note:

* Not optimized for right-to-left (RTL) languages yet
* Multisite is not supported
* Review [our minimum requirements](https://kb.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3)

= WooCommerce emails (Premium) =

**All WooCommerce features are free for stores of 1,000 customers or fewer. [Read More](https://www.mailpoet.com/free-plan/)**

Increase your sales and stay in touch with your customers with our emails for WooCommerce!

With our WooCommerce emails, you can :

* Welcome your new customers when they make their first purchase
* Upsell by sending emails to customers who purchased a specific product or a specific product category
* Convert more customers by reaching those who abandoned their cart
* Reward and engage your customers who have spent a certain amount on your shop (soon)

= Premium details =

**MailPoet Premium is entirely free and includes quality sending for users with 1,000 subscribers or fewer. [Read more.](https://www.mailpoet.com/free-plan/)**

The Premium version adds the following features:

* For each newsletter, see which subscribers opened it and which links got the most clicks
* All WooCommerce emails features
* Removes the small MailPoet logo in the footer of your emails
* Same day support (Monday to Friday)
* Send to over 2,000 subscribers with your own sending method (host, SendGrid, Amazon SES)

See the [short video summary on the Premium](https://kb.mailpoet.com/article/208-video-overview-of-mailpoet-premium)

= MailPoet Sending Service =
**MailPoet Sending Service is free for your first 1,000 subscribers (pay as you go afterwards). [Read More](https://www.mailpoet.com/free-plan/)**

Sending emails and newsletter with your host is not a good idea. You might face sending speed limitations and see your emails ending up in the spam box.

To help your sending go without a hitch, we’ve created an advanced email delivery infrastructure built for WordPress. Our technology allows you to:

* Reach Inboxes, not Spam Boxes
* Send your emails super fast (up to 50,000 emails per hour)
* Get all your emails automatically signed with SPF & DKIM

The MailPoet Sending Service is very easy to setup, you just have to enter a key in your WordPress admin and you’re all set!

= Translations =

* Arabic
* British
* Catalan
* Chinese
* Danish
* Dutch
* French (FR and CA)
* German
* Hungarian
* Italian
* Japanese
* Mexican
* Persian (IR)
* Polish
* Portuguese (BR and PT)
* Russian
* Serbian
* Spanish
* Swedish
* Turkish

We welcome experienced translators to translate directly on [our Transifex project](https://www.transifex.com/wysija/mp3/). Please note that any translations submitted via the "Translating WordPress" web site will not work.

= Security =

[Our repository](https://github.com/mailpoet/mailpoet/) is public on GitHub.

[Security audits are made by LogicalTrust](https://logicaltrust.net/en/), an independent third party.

Have a question for us? Reach us at security@ our domain.

== Frequently Asked Questions ==

Question: Is it MailPoet or Mail poet?
Answer: It’s MailPoet, not mail poet. We’ll answer to either though!

= Need help? =

Stop by our [support site](https://www.mailpoet.com/support).

== Screenshots ==

1. Sample newsletters
2. The drag-and-drop email designer
3. MailPoet email types
4. Newsletter stats (Premium)
5. Subscriber import (via a CSV file or directly from MailChimp)
6. WooCommerce emails

== Changelog ==

= 3.35.1 - 2019-08-20 =
* Improved: remove WooCommerce customers list on non-WooCommerce websites.

= 3.35.0 - 2019-08-13 =
* Improved: background task execution process now uses less CPU to help with CPU-limited hosts. E.g Siteground;
* Improved: WooCommerce functionality is out of beta.
* Fixed: incorrect Linux cron path.
* Fixed: stats emails for post notifications now work;
* Fixed: re-sending confirmation emails no longer increases the count_confirmations row in the database if the sending address isn't authorized.
* Fixed: WordPress and WooCommerce users status can now be manually changed if they're unconfirmed.
* Fixed: php session usage no longer throws a false positive in Health Check that cURL requests timeout.
* Fixed: WooCommerce customer synchronization task to avoid getting stuck on some sites and use excessive CPU;
* Fixed: issue where import loader would get stuck while importing a .csv and copying and pasting emails.

= 3.34.4 - 2019-08-01 =
* Fixed: database connection error with MariaDB and MySQL 8 users.

= 3.34.3 - 2019-07-31 =
* Fixed: some users were experiencing a database issue after latest update.

= 3.34.2 - 2019-07-30 =
* Fixed: issue breaking Email listing page with fatal error.

= 3.34.1 - 2019-07-30 =
* Fixed: issue breaking Email listing page with fatal error.

= 3.34.0 - 2019-07-30 =
* Added: track all WooCommerce revenues generated by your newsletters;
* Added: zero-configuration captcha to protect Mailpoet forms against repeated subscriptions by bots;
* Added: stats email notifications for all automatic emails;
* Improved: education of list hygiene on import;
* Improved: inactive subscriber filtering based on last day subscribed;
* Fixed: API issue creating subscribers;
* Fixed: empty [newsletter:post_title] shortcode output when using category exclusion plugins or functions.

= 3.33.0 - 2019-07-23 =
* Improved: shortcode date formatting;
* Fixed: users who don't use our sending service or double opt-in can now have WP users added as Confirmed;
* Fixed: error when importing subscribers from a MailChimp list;
* Fixed: some users were seeing errors when syncing WooCommerce users due to a mixed-collations database error, it's fixed.

= 3.32.2 - 2019-07-16 =
* Added: notice about inactive subscribers when many users will be marked inactive;
* Improved: filtering out WP spam users from WordPress Users lists.

= 3.32.1 - 2019-07-11 =
* Improved: minor changes and fixes.

= 3.32.0 - 2019-07-09 =
* Improved: messages for undo/redo actions.

= 3.31.1 - 2019-07-03 =
* Improved: minor changes and fixes.

= 3.31.0 - 2019-07-02 =
* Improved: better error reporting and admin messages;
* Improved: known spam registrations to be automatically removed from your "WordPress Users" list;
* Improved: rendering shortcodes in Stats emails;
* Fixed: WordPress users to require confirmed opt-in to join your subscribers list.

= 3.30.0 - 2019-06-25 =
* Added: emails can be sent without stopping for certain error types;
* Added: by popular demand, undo/redo! Use our UI or keyboard shortcuts.
* Improved: disallow import of role addresses, e.g. postmaster@, to improve deliverability;
* Improved: notification for users with many inactive subscriber, alerting them that users are being changed to inactive;
* improved: stats page readability;
* Improved: bounce sync timing for MSS users;
* Fixed: misbehaving MailPoet icon in WP Admin now stops hiding itself and other icons;
* Fixed: some admins were receiving new subscriber notifications twice;
* Fixed: empty posts widget can now be deleted;
* Fixed: email rendering was broken for some users when using particular link types;
* Fixed: display error in sign-up form.

= 3.29.0 - 2019-06-18 =
* Fixed: improved timing of subscribing via the WP registration form to reject subscribers rejected by WP registration protection. Special thanks to customer David for helping troubleshoot this issue.

= 3.28.0 - 2019-06-04 =
* Added: enforcement for authorized sending address for automatic and scheduled emails for new users after March 5;
* Improvement: authorized sending email enforcement for new users since March 5;
* Fixed: error when using single or double quotes in sender name.

= 3.27.0 - 2019-05-28 =
* Added: API documentation for developers on github.com/mailpoet/mailpoet;
* Fixed: email editor's text editing not working due to TinyMCE conflict with some plugins;
* Fixed: some translations that could previously be only in English;
* Fixed: duplicating scheduled newsletters to not include the original scheduled date.

= 3.26.1 - 2019-05-21 =
* Added: Woo Commerce customers now have their own list;
* Improved: users can now scroll through newsletter content while settings sidebar is open;
* Fixed: sign-up confirmation no longer overwritten by default sender on page refresh;
* Fixed: edge cases where blank post notification emails were being sent;
* Fixed: imported subscribers from MP2 no longer marked inactive by default.

= 3.26.0 - 2019-05-14 =
* Improved: minor change of default email confirmation text;
* Improved: WooCommerce customer sync with MailPoet lists for stores with 30k or larger customer lists;
* Fixed: edge case bug where a user with a user_id of 0 would break links in newsletters for some users;
* Fixed: uncaught TypeError related to deleted segments.

= 3.25.1 - 2019-05-06 =
* Improved: subscriber import speed for users importing many subscribers with many custom fields;
* Fixed: typo in Product widget;
* Fixed: conflict with POJO themes in form editor;
* Fixed: backwards compatibility between wpdb:parse_db_host and WP versions earlier than 4.9;
* Fixed: bounced subscribers will continue being synchronized when sending is paused and MailPoet Sending Service is used;
* Fixed: conflict with Thrive Leads where confirmation emails were not being sent.

= 3.25.0 - 2019-04-29 =
* Improved: ALC post fetching consistency;
* Improved: template preview display;
* Improved: now able to turn off inactive subscribers feature;
* Improved: WooCommerce product widget shows price by default now;
* Improved: updated links to knowledge base for bounced and inactive users;
* Improved: clarified how subscribers display within lists when unsubscribed;
* Fixed: issue where bounce task would not run after a failure;
* Fixed: scheduling calendar respects "week starts on" WordPress setting;
* Fixed: links with special characters in email body no longer break sending;
* Fixed: conflict with third-party plugins that was displaying private posts in ALC blocks.

= 3.24.0 - 2019-04-23 =
* Added: add WooCommerce product blocks to your email;
* Added: an optional way to automatically deactivate inactive subscribers who don't read your emails;
* Added: setting to stop sending for inactive subscribers who haven't opened newsletters in a span of time.

= 3.23.2 - 2019-04-16 =
* Improved: UI clarity and user-friendliness;
* Improved: security of the plugin. Thanks to Jan van der Put and Harm Blankers of REQON Security for the report!
* Improved: UX for stats reporting emails;
* Fixed: subscription confirmation email to include a plain text version of the email. Thanks Mathieu!

= 3.23.1 - 2019-04-08 =
* Added: new email type icons;
* Improved: clearer steps in welcome wizard;
* Fixed: added missing translation string;
* Fixed: previewing unsubscribe page no longer unsubscribes the viewer;
* Fixed: form validation error message translation strings.

= 3.23.0 - 2019-04-02 =
* Added: 12 fresh new templates;
* Improved: mouse over highlights entire text block instead of partially;
* Fixed: post titles with single and double quotes break email rendering in ALC and Post blocks;
* Fixed: "import again" subscriber import errors fixed;
* Fixed: Twig conflicts with third party plugins.
* Fixed: import subscribers with custom fields no longer fails;
* Fixed: social icon margins;
* Fixed: updating an imported subscriber no longer triggers welcome email.

= 3.22.0 - 2019-03-26 =
* Improved: minor tweaks and fixes, special thanks to valdrinkoshi for a very helpful PR;
* Improved: admin notices for authorizing FROM addresses;
* Fixed: German umlaut characters no longer break JSON encoding and sending on some hosts. Thanks Oliver and others;
* Fixed: increased limit for visible custom fields in form editor to 40;
* Fixed: sending post notifications with "Monthly on the..." setting.

= 3.21.1 - 2019-03-18 =
* Improved: better highlighting when resizing widgets in editor;
* Improved: sending with consistent FROM address;
* Fixed: db connection issues for connections via socket. Thanks Nicolas!
* Fixed: react console warnings when sending is paused.

= 3.21.0 - 2019-03-11 =
* Added: backwards compatibility method to fix 3rd party integrations;
* Added: option to position the title of your post above the excerpt;
* Added: change the default line heights in Styles sidebar;
* Improved: human readable error message when mail mail function fails;
* Fixed: incorrect "authorize your address" link in plugin.

= 3.20.0 - 2019-03-05 =
* Added: requirement for all "from" email addresses to be authorized to enable sending;
* Added: WooCommerce revenues in stats email notifications;
* Added: new image for WordPress repo;
* Improved: adjustments for third-party plugins who do not integrate MailPoet correctly;
* Improved: email type selection CSS improved to prevent issues with some languages;
* Fixed: double elements in form editor;
* Fixed: MailPoet Sending Service can be activated with a key that has an 'expiring' status;
* Fixed: display bug for 1:2 and 2:1 column layouts in editor;
* Fixed: pagination controls on listings pages.

= 3.19.3 - 2019-02-26 =
* Added: new step in import to educate users about good sending practices during subscriber import;
* Improved: editor controls;
* Fixed: issue with duplicating ALC posts.

= 3.19.2 - 2019-02-19 =
* Added: 13 brand new templates;
* Improved: TinyMCE is hidden on mouse drag;
* Improved: block and widget controls are hidden on mouse drag;
* Fixed: cursor position does not get lost with long text on Chrome;
* Fixed: Mailpoet icon in the Members plugin looks good again.

= 3.19.1 - 2019-02-12 =
* Added: warning against using free email address in "from" fields;
* Added: updated Instagram icons;
* Added: new custom font choices;
* Improved: new design for block controls;
* Improved: updated width of image setting width input field for better display of 4-digit numbers;
* Improved: wider vertical drag button for dividers;
* Improved: align social icons left, center or right;
* Improved: minor enhancement to controls of elements in editor;
* Fixed: restored missing X to modal when deleting templates;
* Fixed: minor adjustments to assist third-party plugins using MailPoet integrations incorrectly;
* Fixed: when Post Notification send date/time are left as default, we now create a Post Notification with those settings;
* Fixed: double click on text in TinyMCE keeps formatting.

= 3.19.0 - 2019-02-05 =
* Added: more clarity for image and column block settings. Thanks focus group testers!;
* Added: further subscription limits to avoid subscription confirmation email abuse;
* Updated: MailPoet's logo in footer of emails;
* Fixed: Linux cron fatal error;
* Fixed: JS error with WP 5.0 when adding new form;
* Fixed: buttons in bold show as bold in settings;
* Fixed: handling of small images with a "Full width" option enabled;
* Fixed: link colors in text blocks are correctly shown in the inbox;
* Fixed: announcement sidebar stays closed.

= 3.18.2 - 2019-01-29 =
* Added: by popular demand, new option to receive a summary email of a campaign's open and click rates;
* Added: loading animation on subscription form submission;
* Added: new modal design;
* Added: first steps for a new WooCommerce customer list;
* Improved: new warning before sending from a free address, like Gmail;
* Fixed: issue with some Gutenberg blocks causing a 500 error in ALC with full posts.

= 3.18.1 - 2019-01-22 =
* Added: new assets for WP plugin repo page;
* Added: nine shiny and new templates for standard, post notification, and WooCommerce emails;
* Fixed: button's settings font display fits nicely again;
* Changed: removed the requirement of having the ZIP PHP extension to use MailPoet 3;

= 3.18.0 - 2019-01-15 =
* Fixed: CSS issues in widget settings;
* Fixed: CSS for Beamer icon on mobile;
* Fixed: size slider issue for images without defined dimensions;
* Fixed: images defaulted to centered in ALC blocks displaying full posts;
* Added: poll to check status of user's first send;

= 3.17.2 - 2019-01-08 =
* Fixed: possible conflict with other plugins using webpack. Thanks, Julien;
* Fixed: creating a new WooCommerce email now defaults to the Woo template page tab instead of standard templates tab;

= 3.17.1 - 2018-12-19 =
* Fixed: premium plugin crash; Thanks, Sebastian!

= 3.17.0 - 2018-12-18 =
* Added: new in-app announcements sidebar. Click the carrot to see;
* Added: option to toggle between desktop and mobile in Preview in Browser;
* Improved: minor changes and fixes;
* Fixed: images in ALC blocks set to display with padding may not render correctly on mobile.

= 3.16.3 - 2018-12-13 =
* Fixed: select all once again selects all;
* Fixed: Post Notification emails to include post images for posts created with WordPress 5.0;
* Fixed: restored correct button captions;
* Fixed: after a brief rebellion, post notification history now displays in an orderly fashion again;

= 3.16.2 - 2018-12-11 =
* Added: new post notification default subject to highlight how to use Subject Line variables;
* Improved: minor tweaks and fixes;

= 3.16.1 - 2018-12-05 =
* Added: error message for banned senders;
* Improved: PHP compatibility warning updated to recommend PHP 7.2 or later;
* Improved: Error handling and display;
* Improved: timing of hook actions to avoid conflicts with other plugins;
* Fixed: mailer errors are displayed if they occur when sending a newsletter preview.

= 3.15.0 - 2018-11-27 =
* Improved: plugin ZIP file size is considerably reduced;
* Fixed: sent and scheduled welcome email counts are displayed correctly;
* Fixed: hidden honeypot field in subscription form now also hidden in editor;
* Fixed: email listing renders consistently in PHP 7.0.32;
* Removed: pluggable.php requirement to avoid conflicts with other plugins.

= 3.14.1 - 2018-11-20 =
* Added: show number of sent and scheduled welcome emails on Welcome Emails listing page;
* Improved: naming and organization of template categories;
* Fixed: limits on number of categories and tags which may be selected for ALC increased to 100. Thanks, Radwan!;

= 3.14.0 - 2018-11-13 =
* Added: readme clarified to show we do not support multisite;
* Added: retina-friendly icon;
* Added: expanded GDPR information in plugin UI;
* Added: ten new fonts for use in emails;
* Improved: post notification email logic;
* Fixed: new post notification templates aren't sent without posts;
* Fixed: missing space in listings returned after brief hiatus;
* Fixed: pausing post notification history items to not prevent further post notifications from being sent; Thanks, Mathieu!
* Fixed: JS errors on emails page.

= 3.13.0 - 2018-11-06 =
* Improved: content of default signup confirmation email;
* Changed: sites using PHP 5.6 will get an old version warning due to no longer receiving security updates after December. Please consider upgrading to PHP 7.2!
* Changed: end of support for PHP 5.5. Please upgrade to PHP 7.0 or newer!

= 3.12.1 - 2018-10-30 =
* Added: 2:1 and 1:2 column blocks for further newsletter customization;
* Fixed: conflict with JetPack 6.6 Asset CDN module.

= 3.12.0 - 2018-10-23 =
* Improved: formatting of "from" address for new subscriber emails;
* Fixed: bulk resend of confirmation emails works again;
* Fixed: email deletion error in the sending process. Thanks @jensgoro!
* Fixed: in-app announcement shows properly;
* Fixed: discount notice is now displayed in all places it was meant to;
* Fixed: minor style and text changes to announcements;
* Fixed: in MailPoet API welcome emails are scheduled only if subscriber is confirmed.

= 3.11.5 - 2018-10-17 =
* Fixed: javascript errors resolved;

= 3.11.4 - 2018-10-16 =
* Added: email notifications to administrators when new subscribers subscribe

= 3.11.3 - 2018-10-09 =
* Fixed: Linux cron to work again.

= 3.11.2 - 2018-10-09 =
* Added: Linux cron option for sending emails;
* Fixed: fatal error for admins who are not also subscribers;
* Fixed: minor style fixes;
* Fixed: added missing translation string;
* Fixed: orphaned tasks cleared after subscribers deleted;
* Fixed: minor styling issue on schedule page for Mac Chrome users.

= 3.11.1 - 2018-10-02 =
* Fixed: JS assets caching issues;

= 3.11.0 - 2018-09-25 =
* Added: notice for users who've migrated from MP2 to MP3;
* Added: many new templates for newsletters, welcome emails, notifications, and Woo Commerce;
* Added: improved sending method error handling;
* Improved: onboarding user experience tweaks and improvements;
* Fixed: JS warning in the emails section;
* Fixed: minor translation issues;
* Fixed: welcome emails removed from Premium page, as they're free now.

= 3.10.1 - 2018-09-18 =
* Improved: made some error messages clearer

= 3.10 - 2018-09-11 =
* Changed: welcome emails to new subscribers are now free for everyone!
* Fixed: newsletter footer warning to be displayed if unsubscribe link is missing.

= 3.9.1 - 2018-09-04 =
* Improved: instructions for migrating from MP2 to MP3 clarified;
* Improved: minor style adjustments for migration tool;
* Improved: minor fixes to onboarding intro guide;
* Improved: template page loading times decreased;
* Fixed: resolved javascript warnings on help page status;
* Fixed: subscriber status remains persistent after migration from MP2 to MP3 without sign-up confirmation enabled;

= 3.9.0 - 2018-08-28 =
* Improved: email processing in sending queues is now more resilient to invalid data. Thanks Tara!
* Fixed: replaced WooCommerce image in welcome wizard;
* Fixed: swapped video in welcome wizard with an updated one;
* Fixed: welcome wizard button displays properly for all users;
* Fixed: permission error when bypassing data import after new install or reset;
* Fixed: added indexes to some foreign keys which were missing;
* Fixed: error displaying number of exported users;
* Fixed: export search function restored;
* Fixed: prevent third party APIs from adding data incorrectly via MailPoets API.

= 3.8.6 - 2018-08-21 =
* Improved: compatibility with caching plugins

= 3.8.5 - 2018-08-14 =
* Changed: End of support for PHP 5.3 and 5.4. Please upgrade to PHP 7.0 or newer!
* Added: improved compatibility with sites cached by server
* Added: setup wizard for new users;
* Fixed: plugin activation for new installs to not crash with white screen;
* Fixed: slow sending on sites with a lot of sent newsletters.

= 3.8.4 - 2018-08-07 =
* Added: activation prompt for Mailpoet Sending Service when API key is verified;
* Added: next scheduled tasks now display in sending queue status;
* Added: new additional save button to the top of editor page;

= 3.8.3 - 2018-08-01 =
* Fixed: resolved potential duplicate sending issue.

= 3.8.2 - 2018-07-31 =
* Added: more useful sending status information in Help page.

= 3.8.1 - 2018-07-24 =
* Added: images can be used as backgrounds for column layout blocks;
* Added: notification if cron ping does not work correctly during first sending attempt;
* Added: new, prettier email type icon;
* Added: TLS 1.2 support to Swiftmailer to prevent SMTP sending issues;
* Added: updated error messages coming from the sending service;
* Added: clarified sending tab to encourage using our free sending service;
* Fixed: "Create New Form" link in subscription widget now creates a new form again;
* Fixed: removed call to action for MSS service for users already using MSS.

= 3.8 - 2018-07-17 =
* Fixed: proper spacing between paragraphs in full post is now respected;
* Fixed: deleting users who have opened one newsletter correctly records data for GDPR;
* Fixed: sending tasks are paused when welcome email is deactivated. Thanks, Seng;
* Fixed: can send when default sender is not set;
* Updated: API validation message updated to reflect incompatibilities with localhost.

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

= 3.0.0 - 2017-09-20 =
* Official launch of the new MailPoet. :)
* Improved: MailPoet 3 now works with other plugins that use a supported version of Twig templating engine. Thanks @supsysticcom;
* Added: we now offer a free sending plan for "2000" subscribers or less. Thx MailPoet!

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
