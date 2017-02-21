<?php
namespace MailPoet\Config\PopulatorData\Templates;

if(!defined('ABSPATH')) exit;

class FranksRoastHouseTemplate {

  function __construct($assets_url) {
    $this->assets_url = $assets_url;
    $this->external_template_image_url = '//ps.w.org/mailpoet/assets/newsletter-templates/franks-roast-house';
    $this->social_icon_url = $this->assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Frank's Roast House", 'mailpoet'),
      'description' => __("Think of this as your sandbox. Play around with this example newsletter to see what MailPoet can do for you.", 'mailpoet'),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getBody() {
    return array(
      "content" => array(
        "type" => "container",
        "orientation" => "vertical",
        "styles" => array(
          "block" => array(
            "backgroundColor" => "transparent"
          )
        ),
        "blocks" => array(
          array(
            "orientation" => "horizontal",
            "blocks" => array(
              array(
                "type" => "container",
                "orientation" => "vertical",
                "styles" => array(
                  "block" => array(
                    "backgroundColor" => "transparent"
                  )
                ),
                "blocks" => array(
                  array(
                    "type" => "header",
                    "text" => __("<a href=\"[link:newsletter_view_in_browser_url]\">Open this email in your web browser.</a>", 'mailpoet'),
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "#ccc6c6"
                      ),
                      "text" => array(
                        "fontColor" => "#222222",
                        "fontFamily" => "Arial",
                        "fontSize" => "12px",
                        "textAlign" => "center"
                      ),
                      "link" => array(
                        "fontColor" => "#36251e",
                        "textDecoration" => "underline"
                      )
                    )
                  ),
                  array(
                    "type" => "image",
                    "link" => "http://www.example.com",
                    "src" => $this->external_template_image_url . "/header-v2.jpg",
                    "alt" => __("Frank's Café", 'mailpoet'),
                    "fullWidth" => true,
                    "width" => "600px",
                    "height" => "220px",
                    "styles" => array(
                      "block" => array(
                        "textAlign" => "center"
                      )
                    )
                  ),
                  array(
                    "type" => "spacer",
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "transparent",
                        "height" => "30px"
                      )
                    )
                  ),
                  array(
                    "type" => "text",
                    "text" => __("<p>Hi there [subscriber:firstname | default:coffee drinker]</p>\n<p></p>\n<p>Sit back and enjoy your favorite roast as you read this week's newsletter. </p>", 'mailpoet')
                  ),
                  array(
                    "type" => "image",
                    "link" => "http://example.org",
                    "src" => $this->external_template_image_url . "/coffee-grain.jpg",
                    "alt" => __('Coffee grain', 'mailpoet'),
                    "fullWidth" => false,
                    "width" => "1599px",
                    "height" => "777px",
                    "styles" => array(
                      "block" => array(
                        "textAlign" => "center"
                      )
                    )
                  ),
                  array(
                    "type" => "text",
                    "text" => __("<h1 style=\"text-align: center;\">--- Guest Coffee Roaster: <em>Brew Bros. ---</em></h1>\n<p><em></em></p>\n<p>Visit our Center Avenue store to try the latest guest coffee from Brew Bros, a local coffee roaster. This young duo started only two years ago, but have quickly gained popularity through pop-up shops, local events, and collaborations with food trucks.</p>\n<p></p>\n<blockquote>\n<p><span style=\"color: #ff6600;\"><em>Tasting notes: A rich, caramel flavor with subtle hints of molasses. The perfect wake-up morning espresso!</em></span></p>\n</blockquote>", 'mailpoet')
                  )
                )
              )
            ),
            "type" => "container",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "transparent"
              )
            )
          ),
          array(
            "orientation" => "horizontal",
            "blocks" => array(
              array(
                "type" => "container",
                "orientation" => "vertical",
                "styles" => array(
                  "block" => array(
                    "backgroundColor" => "transparent"
                  )
                ),
                "blocks" => array(
                  array(
                    "type" => "text",
                    "text" => __("<h2>Sandwich Competition</h2>", 'mailpoet')
                  ),
                  array(
                    "type" => "image",
                    "link" => "http://example.org",
                    "src" => $this->external_template_image_url . "/sandwich.jpg",
                    "alt" => __('Sandwich', 'mailpoet'),
                    "fullWidth" => false,
                    "width" => "640px",
                    "height" => "344px",
                    "styles" => array(
                      "block" => array(
                        "textAlign" => "center"
                      )
                    )
                  ),
                  array(
                    "type" => "text",
                    "text" => __("<p>Have an idea for the Next Great Sandwich? Tell us! We're offering free lunch for a month if you can invent an awesome new sandwich for our menu.</p>\n<p></p>\n<p></p>\n<p>Simply tweet your ideas to <a href=\"http://www.example.com\" title=\"This isn't a real twitter account\">@franksroasthouse</a> and use #sandwichcomp and we'll let you know if you're a winner.</p>", 'mailpoet')
                  ),
                  array(
                    "type" => "button",
                    "text" => ("Find out more"),
                    "url" => "http://example.org",
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "#047da7",
                        "borderColor" => "#004a68",
                        "borderWidth" => "1px",
                        "borderRadius" => "3px",
                        "borderStyle" => "solid",
                        "width" => "180px",
                        "lineHeight" => "34px",
                        "fontColor" => "#ffffff",
                        "fontFamily" => "Arial",
                        "fontSize" => "14px",
                        "fontWeight" => "normal",
                        "textAlign" => "center"
                      )
                    )
                  ),
                  array(
                    "type" => "text",
                    "text" => __("<h3 style=\"text-align: center;\">Follow Us</h3>", 'mailpoet')
                  ),
                  array(
                    "type" => "social",
                    "iconSet" => "full-symbol-black",
                    "icons" => array(
                      array(
                        "type" => "socialIcon",
                        "iconType" => "facebook",
                        "link" => "http://www.facebook.com/mailpoetplugin",
                        "image" => $this->social_icon_url . "/07-full-symbol-black/Facebook.png",
                        "height" => "32px",
                        "width" => "32px",
                        "text" => "Facebook"
                      ),
                      array(
                        "type" => "socialIcon",
                        "iconType" => "twitter",
                        "link" => "http://www.twitter.com/mailpoet",
                        "image" => $this->social_icon_url . "/07-full-symbol-black/Twitter.png",
                        "height" => "32px",
                        "width" => "32px",
                        "text" => "Twitter"
                      ),
                      array(
                        "type" => "socialIcon",
                        "iconType" => "instagram",
                        "link" => "http://www.instagram.com/test",
                        "image" => $this->social_icon_url . "/07-full-symbol-black/Instagram.png",
                        "height" => "32px",
                        "width" => "32px",
                        "text" => "Instagram"
                      ),
                      array(
                        "type" => "socialIcon",
                        "iconType" => "website",
                        "link" => "http://www.mailpoet.com",
                        "image" => $this->social_icon_url . "/07-full-symbol-black/Website.png",
                        "height" => "32px",
                        "width" => "32px",
                        "text" => "Website"
                      )
                    )
                  )
                )
              ),
              array(
                "type" => "container",
                "orientation" => "vertical",
                "styles" => array(
                  "block" => array(
                    "backgroundColor" => "transparent"
                  )
                ),
                "blocks" => array(
                  array(
                    "type" => "text",
                    "text" => __("<h2>New Store Opening!</h2>", 'mailpoet')
                  ),
                  array(
                    "type" => "image",
                    "link" => "http://example.org",
                    "src" => $this->external_template_image_url . "/map-v2.jpg",
                    "alt" => __('Map', 'mailpoet'),
                    "fullWidth" => false,
                    "width" => "636px",
                    "height" => "342px",
                    "styles" => array(
                      "block" => array(
                        "textAlign" => "center"
                      )
                    )
                  ),
                  array(
                    "type" => "text",
                    "text" => __("<p>Watch out Broad Street, we're coming to you very soon! </p>\n<p></p>\n<p>Keep an eye on your inbox, as we'll have some special offers for our email subscribers plus an exclusive launch party invite!<br /><br /></p>", 'mailpoet')
                  ),
                  array(
                    "type" => "text",
                    "text" => __("<h2>New and Improved Hours!</h2>\n<p></p>\n<p>Frank's is now open even later, so you can get your caffeine fix all day (and night) long! Here's our new opening hours:</p>\n<p></p>\n<ul>\n<li>Monday - Thursday: 6am - 12am</li>\n<li>Friday - Saturday: 6am - 1:30am</li>\n<li>Sunday: 7:30am - 11pm</li>\n</ul>", 'mailpoet')
                  )
                )
              )
            ),
            "type" => "container",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "#ebebeb"
              )
            )
          ),
          array(
            "orientation" => "horizontal",
            "blocks" => array(
              array(
                "type" => "container",
                "orientation" => "vertical",
                "styles" => array(
                  "block" => array(
                    "backgroundColor" => "transparent"
                  )
                ),
                "blocks" => array(
                  array(
                    "type" => "footer",
                    "text" => __("<p><a href=\"[link:subscription_unsubscribe_url]\">Unsubscribe</a> | <a href=\"[link:subscription_manage_url]\">Manage subscription</a><br />12345 MailPoet Drive, EmailVille, 76543</p>", 'mailpoet'),
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "#a9a7a7"
                      ),
                      "text" => array(
                        "fontColor" => "#000000",
                        "fontFamily" => "Arial",
                        "fontSize" => "12px",
                        "textAlign" => "center"
                      ),
                      "link" => array(
                        "fontColor" => "#000000",
                        "textDecoration" => "underline"
                      )
                    )
                  )
                )
              )
            ),
            "type" => "container",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "transparent"
              )
            )
          )
        )
      ),
      "globalStyles" => array(
        "text" => array(
          "fontColor" => "#000000",
          "fontFamily" => "Arial",
          "fontSize" => "14px"
        ),
        "h1" => array(
          "fontColor" => "#604b4b",
          "fontFamily" => "Lucida",
          "fontSize" => "30px"
        ),
        "h2" => array(
          "fontColor" => "#047da7",
          "fontFamily" => "Lucida",
          "fontSize" => "22px"
        ),
        "h3" => array(
          "fontColor" => "#333333",
          "fontFamily" => "Georgia",
          "fontSize" => "20px"
        ),
        "link" => array(
          "fontColor" => "#047da7",
          "textDecoration" => "underline"
        ),
        "wrapper" => array(
          "backgroundColor" => "#ffffff"
        ),
        "body" => array(
          "backgroundColor" => "#ccc6c6"
        )
      )
    );
  }

  private function getThumbnail() {
    return $this->external_template_image_url . '/screenshot.jpg';
  }

}
