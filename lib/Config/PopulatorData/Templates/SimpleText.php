<?php
namespace MailPoet\Config\PopulatorData\Templates;

if(!defined('ABSPATH')) exit;

class SimpleText {

  function __construct($assets_url) {
    $this->assets_url = $assets_url;
    $this->external_template_image_url = '//ps.w.org/mailpoet/assets/newsletter-templates/simple-text';
    $this->template_image_url = $this->assets_url . '/img/blank_templates';
    $this->social_icon_url = $this->assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Simple Text", 'mailpoet'),
      'description' => __("A simple plain text template - just like a regular email.", 'mailpoet'),
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
                    "alt" => __("Fake logo", 'mailpoet'),
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
                    "text" => __("<p style=\"text-align: left;\">Hi [subscriber:firstname | default:subscriber],</p>\n<p style=\"text-align: left;\"></p>\n<p style=\"text-align: left;\">In MailPoet, you can write emails in plain text, just like in a regular email. This can make your email newsletters more personal and attention-grabbing.</p>\n<p style=\"text-align: left;\"></p>\n<p style=\"text-align: left;\">Is this too simple? You can still style your text with basic formatting, like <strong>bold</strong> or <em>italics.</em></p>\n<p style=\"text-align: left;\"></p>\n<p style=\"text-align: left;\">Finally, you can also add a call-to-action button between 2 blocks of text, like this:</p>", 'mailpoet')
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
                        "height" => "23px"
                      )
                    )
                  ),
                  array(
                    "type" => "button",
                    "text" => __("It's time to take action!", 'mailpoet'),
                    "url" => "",
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "#2ea1cd",
                        "borderColor" => "#0074a2",
                        "borderWidth" => "1px",
                        "borderRadius" => "5px",
                        "borderStyle" => "solid",
                        "width" => "288px",
                        "lineHeight" => "40px",
                        "fontColor" => "#ffffff",
                        "fontFamily" => "Verdana",
                        "fontSize" => "16px",
                        "fontWeight" => "normal",
                        "textAlign" => "left"
                      )
                    )
                  ),
                  array(
                    "type" => "text",
                    "text" => __("<p>Thanks for reading. See you soon!</p>\n<p></p>\n<p><strong><em>The MailPoet Team</em></strong></p>", 'mailpoet')
                  ),
                  array(
                    "type" => "footer",
                    "text" => __("<p><a href=\"[link:subscription_unsubscribe_url]\">Unsubscribe</a> | <a href=\"[link:subscription_manage_url]\">Manage your subscription</a><br />Add your postal address here!</p>", 'mailpoet'),
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "transparent"
                      ),
                      "text" => array(
                        "fontColor" => "#222222",
                        "fontFamily" => "Arial",
                        "fontSize" => "12px",
                        "textAlign" => "left"
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
          "fontSize" => "15px"
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
          "backgroundColor" => "#ffffff"
        )
      )
    );
  }

  private function getThumbnail() {
    return $this->external_template_image_url . '/screenshot.jpg';
  }

}
