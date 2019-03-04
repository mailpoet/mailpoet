<?php
namespace MailPoet\Config\PopulatorData\Templates;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;


class NewsletterBlank13Column {

  private $assets_url;
  private $external_template_image_url;
  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->assets_url = $assets_url;
    $this->external_template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/newsletter-blank-1-3-column';
    $this->template_image_url = $this->assets_url . '/img/blank_templates';
    $this->social_icon_url = $this->assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => WPFunctions::get()->__("Newsletter: Blank 1:3 Column", 'mailpoet'),
      'categories' => json_encode(array('standard')),
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
            "type" => "container",
            "orientation" => "horizontal",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "#f8f8f8"
              )
            ),
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
                    "text" => WPFunctions::get()->__("Display problems? <a href=\"[link:newsletter_view_in_browser_url]\">Open this email in your web browser.</a>", 'mailpoet'),
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "transparent"
                      ),
                      "text" => array(
                        "fontColor" => "#222222",
                        "fontFamily" => "Arial",
                        "fontSize" => "12px",
                        "textAlign" => "center"
                      ),
                      "link" => array(
                        "fontColor" => "#6cb7d4",
                        "textDecoration" => "underline"
                      )
                    )
                  )
                )
              )
            )
          ),
          array(
            "type" => "container",
            "orientation" => "horizontal",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "#ffffff"
              )
            ),
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
                    "type" => "spacer",
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "transparent",
                        "height" => "30px"
                      )
                    )
                  ),
                  array(
                    "type" => "image",
                    "link" => "",
                    "src" => $this->template_image_url . "/fake-logo.png",
                    "alt" => WPFunctions::get()->__("Fake logo", 'mailpoet'),
                    "fullWidth" => false,
                    "width" => "598px",
                    "height" => "71px",
                    "styles" => array(
                      "block" => array(
                        "textAlign" => "center"
                      )
                    )
                  ),
                  array(
                    "type" => "text",
                    "text" => WPFunctions::get()->__("<h1 style=\"text-align: center;\"><strong>Let's Get Started! </strong></h1>\n<p>&nbsp;</p>\n<p>It's time to design your newsletter! In the right sidebar, you'll find four menu items that will help you customize your newsletter:</p>\n<ol>\n<li>Content</li>\n<li>Columns</li>\n<li>Styles</li>\n<li>Preview</li>\n</ol>", 'mailpoet')
                  ),
                  array(
                    "type" => "divider",
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "transparent",
                        "padding" => "13px",
                        "borderStyle" => "dotted",
                        "borderWidth" => "3px",
                        "borderColor" => "#aaaaaa"
                      )
                    )
                  )
                )
              )
            )
          ),
          array(
            "type" => "container",
            "orientation" => "horizontal",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "transparent"
              )
            ),
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
                    "text" => WPFunctions::get()->__("<h3>This template... </h3>", 'mailpoet')
                  ),
                  array(
                    "type" => "text",
                    "text" => WPFunctions::get()->__("<p>In the right sidebar, you can add layout blocks to your newsletter.</p>", 'mailpoet')
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
                    "text" => WPFunctions::get()->__("<h3>... has a... </h3>", 'mailpoet')
                  ),
                  array(
                    "type" => "text",
                    "text" => WPFunctions::get()->__("<p>You have the choice of:</p>\n<ul>\n<li>1 column</li>\n<li>2 columns</li>\n<li>3 columns</li>\n</ul>", 'mailpoet')
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
                    "text" => WPFunctions::get()->__("<h3>3-column layout.</h3>", 'mailpoet')
                  ),
                  array(
                    "type" => "text",
                    "text" => WPFunctions::get()->__("<p>You can add as many layout blocks as you want!</p>", 'mailpoet')
                  ),
                  array(
                    "type" => "text",
                    "text" => ""
                  )
                )
              )
            )
          ),
          array(
            "type" => "container",
            "orientation" => "horizontal",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "#f8f8f8"
              )
            ),
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
                    "type" => "divider",
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "transparent",
                        "padding" => "24.5px",
                        "borderStyle" => "solid",
                        "borderWidth" => "3px",
                        "borderColor" => "#aaaaaa"
                      )
                    )
                  ),
                  array(
                    "type" => "social",
                    "iconSet" => "grey",
                    "icons" => array(
                      array(
                        "type" => "socialIcon",
                        "iconType" => "facebook",
                        "link" => "http://www.facebook.com",
                        "image" => $this->social_icon_url . "/02-grey/Facebook.png",
                        "height" => "32px",
                        "width" => "32px",
                        "text" => "Facebook"
                      ),
                      array(
                        "type" => "socialIcon",
                        "iconType" => "twitter",
                        "link" => "http://www.twitter.com",
                        "image" => $this->social_icon_url . "/02-grey/Twitter.png",
                        "height" => "32px",
                        "width" => "32px",
                        "text" => "Twitter"
                      )
                    )
                  ),
                  array(
                    "type" => "divider",
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "transparent",
                        "padding" => "7.5px",
                        "borderStyle" => "solid",
                        "borderWidth" => "3px",
                        "borderColor" => "#aaaaaa"
                      )
                    )
                  ),
                  array(
                    "type" => "footer",
                    "text" => WPFunctions::get()->__("<p><a href=\"[link:subscription_unsubscribe_url]\">Unsubscribe</a> | <a href=\"[link:subscription_manage_url]\">Manage your subscription</a><br />Add your postal address here!</p>", 'mailpoet'),
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "transparent"
                      ),
                      "text" => array(
                        "fontColor" => "#222222",
                        "fontFamily" => "Arial",
                        "fontSize" => "12px",
                        "textAlign" => "center"
                      ),
                      "link" => array(
                        "fontColor" => "#6cb7d4",
                        "textDecoration" => "none"
                      )
                    )
                  )
                )
              )
            )
          )
        )
      ),
      "globalStyles" => array(
        "text" => array(
          "fontColor" => "#000000",
          "fontFamily" => "Arial",
          "fontSize" => "16px"
        ),
        "h1" => array(
          "fontColor" => "#111111",
          "fontFamily" => "Trebuchet MS",
          "fontSize" => "30px"
        ),
        "h2" => array(
          "fontColor" => "#222222",
          "fontFamily" => "Trebuchet MS",
          "fontSize" => "24px"
        ),
        "h3" => array(
          "fontColor" => "#333333",
          "fontFamily" => "Trebuchet MS",
          "fontSize" => "22px"
        ),
        "link" => array(
          "fontColor" => "#21759B",
          "textDecoration" => "underline"
        ),
        "wrapper" => array(
          "backgroundColor" => "#ffffff"
        ),
        "body" => array(
          "backgroundColor" => "#eeeeee"
        )
      )
    );
  }

  private function getThumbnail() {
    return $this->external_template_image_url . '/screenshot.jpg';
  }

}


