<?php
namespace MailPoet\Config\PopulatorData\Templates;

if(!defined('ABSPATH')) exit;

class StoreDiscount {

  function __construct($assets_url) {
    $this->assets_url = $assets_url;
    $this->external_template_image_url = '//ps.w.org/mailpoet/assets/newsletter-templates/store-discount';
    $this->template_image_url = $this->assets_url . '/img/sample_templates/discount';
    $this->social_icon_url = $this->assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Store Discount", 'mailpoet'),
      'description' => __("Store discount email with coupon and shopping suggestions", 'mailpoet'),
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
        "blocks" => array(array(
          "type" => "container",
          "orientation" => "horizontal",
          "styles" => array(
            "block" => array(
              "backgroundColor" => "transparent"
            )
          ),
          "blocks" => array(array(
            "type" => "container",
            "orientation" => "vertical",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "transparent"
              )
            ),
            "blocks" => array(array(
              "type" => "spacer",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent",
                  "height" => "20px"
                )
              )
            ), array(
              "type" => "image",
              "link" => "",
              "src" => $this->external_template_image_url . "/bicycle-header3.png",
              "alt" => __("bicycle-header3", 'mailpoet'),
              "fullWidth" => false,
              "width" => "423px",
              "height" => "135px",
              "styles" => array(
                "block" => array(
                  "textAlign" => "center"
                )
              )
            ), array(
              "type" => "text",
              "text" => __("<p></p>\n<p>Hi [subscriber:firstname | default:reader]</p>\n<p class=\"\"></p>\n<p>Fancy 15% off your next order? Use this coupon on any product in our store. Expires Wednesday! To apply the discount, enter the code on the payments page.</p>", 'mailpoet')
            ), array(
              "type" => "spacer",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent",
                  "height" => "20px"
                )
              )
            ))
          ))
        ), array(
          "type" => "container",
          "orientation" => "horizontal",
          "styles" => array(
            "block" => array(
              "backgroundColor" => "#ebdddd"
            )
          ),
          "blocks" => array(array(
            "type" => "container",
            "orientation" => "vertical",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "transparent"
              )
            ),
            "blocks" => array(array(
              "type" => "divider",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent",
                  "padding" => "16px",
                  "borderStyle" => "dashed",
                  "borderWidth" => "2px",
                  "borderColor" => "#9a5fa1"
                )
              )
            ), array(
              "type" => "text",
              "text" => __("<h1 style=\"text-align: center;\"><em><strong>Get a 15% off your next order</strong></em></h1>", 'mailpoet')
            ), array(
              "type" => "text",
              "text" => __("<h2 style=\"text-align: center;\"><strong>USE CODE: WELOVEMAILPOET</strong></h2>", 'mailpoet')
            ), array(
              "type" => "divider",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent",
                  "padding" => "16px",
                  "borderStyle" => "dashed",
                  "borderWidth" => "2px",
                  "borderColor" => "#9a5fa1"
                )
              )
            ))
          ))
        ), array(
          "type" => "container",
          "orientation" => "horizontal",
          "styles" => array(
            "block" => array(
              "backgroundColor" => "transparent"
            )
          ),
          "blocks" => array(array(
            "type" => "container",
            "orientation" => "vertical",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "transparent"
              )
            ),
            "blocks" => array(array(
              "type" => "spacer",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent",
                  "height" => "20px"
                )
              )
            ), array(
              "type" => "button",
              "text" => __("SHOP NOW", 'mailpoet'),
              "url" => "",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "#9a5fa1",
                  "borderColor" => "#854f8b",
                  "borderWidth" => "3px",
                  "borderRadius" => "5px",
                  "borderStyle" => "solid",
                  "width" => "288px",
                  "lineHeight" => "50px",
                  "fontColor" => "#ffffff",
                  "fontFamily" => "Verdana",
                  "fontSize" => "18px",
                  "fontWeight" => "normal",
                  "textAlign" => "center"
                )
              )
            ), array(
              "type" => "text",
              "text" => __("<h1 style=\"text-align: center;\"><strong><em>Use your discount on these great products...</em></strong></h1>", 'mailpoet')
            ), array(
              "type" => "spacer",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent",
                  "height" => "20px"
                )
              )
            ))
          ))
        ), array(
          "type" => "container",
          "orientation" => "horizontal",
          "styles" => array(
            "block" => array(
              "backgroundColor" => "transparent"
            )
          ),
          "blocks" => array(array(
            "type" => "container",
            "orientation" => "vertical",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "transparent"
              )
            ),
            "blocks" => array(array(
              "type" => "image",
              "link" => "",
              "src" => $this->external_template_image_url . "/red-icycle.jpg",
              "alt" => __("red-bicycle", 'mailpoet'),
              "fullWidth" => false,
              "width" => "558px",
              "height" => "399px",
              "styles" => array(
                "block" => array(
                  "textAlign" => "center"
                )
              )
            ), array(
              "type" => "text",
              "text" => __("<h3 style=\"text-align: center;\">Lovely Red Bicycle</h3>\n<p>What can we say? It's a totally awesome red bike, and it's the first of its kind in our collection. No sweat!</p>\n<h3 style=\"text-align: center;\"><strong><span style=\"color: #488e88;\">$289.99</span></strong></h3>", 'mailpoet')
            ), array(
              "type" => "button",
              "text" => __("Buy", 'mailpoet'),
              "url" => "",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "#9a5fa1",
                  "borderColor" => "#854f8b",
                  "borderWidth" => "3px",
                  "borderRadius" => "5px",
                  "borderStyle" => "solid",
                  "width" => "180px",
                  "lineHeight" => "40px",
                  "fontColor" => "#ffffff",
                  "fontFamily" => "Verdana",
                  "fontSize" => "18px",
                  "fontWeight" => "normal",
                  "textAlign" => "center"
                )
              )
            ))
          ), array(
            "type" => "container",
            "orientation" => "vertical",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "transparent"
              )
            ),
            "blocks" => array(array(
              "type" => "image",
              "link" => "",
              "src" => $this->external_template_image_url . "/orange-bicycle.jpg",
              "alt" => __("orange-bicycle", 'mailpoet'),
              "fullWidth" => false,
              "width" => "639px",
              "height" => "457px",
              "styles" => array(
                "block" => array(
                  "textAlign" => "center"
                )
              )
            ), array(
              "type" => "text",
              "text" => __("<h3 style=\"text-align: center;\">Little Orange Bicycle</h3>\n<p>Another product that's just as awesome but it's the second type, and more orange, with some blue. Cool beans!</p>\n<h3 style=\"line-height: 22.4px; text-align: center;\"><span style=\"color: #488e88;\"><strong>$209.99</strong></span></h3>", 'mailpoet')
            ), array(
              "type" => "button",
              "text" => __("Buy", 'mailpoet'),
              "url" => "",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "#9a5fa1",
                  "borderColor" => "#854f8b",
                  "borderWidth" => "3px",
                  "borderRadius" => "5px",
                  "borderStyle" => "solid",
                  "width" => "180px",
                  "lineHeight" => "40px",
                  "fontColor" => "#ffffff",
                  "fontFamily" => "Verdana",
                  "fontSize" => "18px",
                  "fontWeight" => "normal",
                  "textAlign" => "center"
                )
              )
            ))
          ))
        ), array(
          "type" => "container",
          "orientation" => "horizontal",
          "styles" => array(
            "block" => array(
              "backgroundColor" => "transparent"
            )
          ),
          "blocks" => array(array(
            "type" => "container",
            "orientation" => "vertical",
            "styles" => array(
              "block" => array(
                "backgroundColor" => "transparent"
              )
            ),
            "blocks" => array(array(
              "type" => "spacer",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent",
                  "height" => "22px"
                )
              )
            ), array(
              "type" => "divider",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent",
                  "padding" => "20px",
                  "borderStyle" => "solid",
                  "borderWidth" => "1px",
                  "borderColor" => "#9e9e9e"
                )
              )
            ), array(
              "type" => "text",
              "text" => __("<p><em>Terms and Conditions:</em></p>\n<ul>\n<li><span style=\"line-height: 1.6em; background-color: inherit;\">Must be used by midnight EST December 15 2036.</span></li>\n<li><span style=\"line-height: 1.6em; background-color: inherit;\">Discount does not include shipping.</span></li>\n<li><span style=\"line-height: 1.6em; background-color: inherit;\">Cannot be used in conjunction with any other offer.</span></li>\n</ul>", 'mailpoet')
            ), array(
              "type" => "social",
              "iconSet" => "grey",
              "icons" => array(array(
                "type" => "socialIcon",
                "iconType" => "facebook",
                "link" => "",
                "image" => $this->social_icon_url . "/02-grey/Facebook.png",
                "height" => "32px",
                "width" => "32px",
                "text" => __("Facebook", 'mailpoet')
              ), array(
                "type" => "socialIcon",
                "iconType" => "twitter",
                "link" => "",
                "image" => $this->social_icon_url . "/02-grey/Twitter.png",
                "height" => "32px",
                "width" => "32px",
                "text" => __("Twitter", 'mailpoet')
              ))
            ), array(
              "type" => "footer",
              "text" => __("<p><a href=\"[link:subscription_unsubscribe_url]\">Unsubscribe</a> | <a href=\"[link:subscription_manage_url]\">Manage subscription</a></p>\n<p>1 Store Street, Shopville, CA 1345</p>", 'mailpoet'),
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent"
                ),
                "text" => array(
                  "fontColor" => "#343434",
                  "fontFamily" => "Verdana",
                  "fontSize" => "12px",
                  "textAlign" => "center"
                ),
                "link" => array(
                  "fontColor" => "#488e88",
                  "textDecoration" => "none"
                )
              )
            ))
          ))
        ))
      ),
      "globalStyles" => array(
        "text" => array(
          "fontColor" => "#343434",
          "fontFamily" => "Verdana",
          "fontSize" => "14px"
        ),
        "h1" => array(
          "fontColor" => "#488e88",
          "fontFamily" => "Trebuchet MS",
          "fontSize" => "22px"
        ),
        "h2" => array(
          "fontColor" => "#9a5fa1",
          "fontFamily" => "Verdana",
          "fontSize" => "24px"
        ),
        "h3" => array(
          "fontColor" => "#9a5fa1",
          "fontFamily" => "Trebuchet MS",
          "fontSize" => "18px"
        ),
        "link" => array(
          "fontColor" => "#488e88",
          "textDecoration" => "underline"
        ),
        "wrapper" => array(
          "backgroundColor" => "#afe3de"
        ),
        "body" => array(
          "backgroundColor" => "#afe3de"
        )
      )
    );
  }

  private function getThumbnail() {
    return $this->external_template_image_url . '/screenshot.jpg';
  }

}
