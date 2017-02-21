<?php
namespace MailPoet\Config\PopulatorData\Templates;

if(!defined('ABSPATH')) exit;

class Restaurant {

  function __construct($assets_url) {
    $this->assets_url = $assets_url;
    $this->external_template_image_url = '//ps.w.org/mailpoet/assets/newsletter-templates/restaurant';
    $this->template_image_url = $this->assets_url . '/img/sample_templates/restaurant';
    $this->social_icon_url = $this->assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Restaurant", 'mailpoet'),
      'description' => __("What's fresh on the menu?", 'mailpoet'),
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
              "type" => "image",
              "link" => "",
              "src" => $this->external_template_image_url . "/header.jpg",
              "alt" => "Joe's Burger Joint",
              "fullWidth" => true,
              "width" => "660px",
              "height" => "100px",
              "styles" => array(
                "block" => array(
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
              "type" => "image",
              "link" => "",
              "src" => $this->external_template_image_url . "/burger.jpg",
              "alt" => "burger",
              "fullWidth" => true,
              "width" => "1127px",
              "height" => "945px",
              "styles" => array(
                "block" => array(
                  "textAlign" => "center"
                )
              )
            ), array(
              "type" => "spacer",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent",
                  "height" => "30px"
                )
              )
            ), array(
              "type" => "button",
              "text" => "Make a reservation",
              "url" => "",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "#d83b3b",
                  "borderColor" => "#ffffff",
                  "borderWidth" => "0px",
                  "borderRadius" => "0px",
                  "borderStyle" => "solid",
                  "width" => "225px",
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
              "text" => "<h1 style=\"text-align: center;\"><em>Upgrade! Add these sides</em></h1>"
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
              "src" => $this->external_template_image_url . "/boyga-1329911-639x852.jpg",
              "alt" => "boyga-1329911-639x852",
              "fullWidth" => false,
              "width" => "639px",
              "height" => "852px",
              "styles" => array(
                "block" => array(
                  "textAlign" => "center"
                )
              )
            ), array(
              "type" => "text",
              "text" => "<blockquote>\n<p>These onion rings have the perfect crispy batter! - Hayley King, Daily News</p>\n</blockquote>"
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
              "src" => $this->external_template_image_url . "/macaroni-w-salad-1323787.jpg",
              "alt" => "Macaroni salad",
              "fullWidth" => false,
              "width" => "600px",
              "height" => "800px",
              "styles" => array(
                "block" => array(
                  "textAlign" => "center"
                )
              )
            ), array(
              "type" => "text",
              "text" => "<blockquote>\n<p>NEW! Ultimate Mac &amp; Cheese Salad. Available at all locations</p>\n</blockquote>"
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
              "type" => "divider",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent",
                  "padding" => "29px",
                  "borderStyle" => "dashed",
                  "borderWidth" => "3px",
                  "borderColor" => "#aaaaaa"
                )
              )
            ))
          ))
        ), array(
          "type" => "container",
          "orientation" => "horizontal",
          "styles" => array(
            "block" => array(
              "backgroundColor" => "#e0e0e0"
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
              "type" => "text",
              "text" => "<h2 style=\"text-align: center;\">Find us at these locations</h2>"
            ))
          ))
        ), array(
          "type" => "container",
          "orientation" => "horizontal",
          "styles" => array(
            "block" => array(
              "backgroundColor" => "#e0e0e0"
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
              "type" => "text",
              "text" => "<h3><span style=\"text-decoration: underline;\"><em>Denver</em></span></h3>\n<p>1263 Schoville Street</p>\n<p>53355 DENVER</p>\n<p>CO</p>"
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
              "type" => "text",
              "text" => "<h3><span style=\"text-decoration: underline;\"><em>Fort Collins</em></span></h3>\n<p><em></em>157 Maine Street</p>\n<p>86432 FORT COLLINS<br />CO</p>\n<p></p>"
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
              "type" => "text",
              "text" => "<h3><span style=\"text-decoration: underline;\"><em>Pueblo</em></span></h3>\n<p><em></em>5390 York Avenue</p>\n<p>64297 Pueblo</p>\n<p>CO</p>"
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
                  "height" => "30px"
                )
              )
            ), array(
              "type" => "social",
              "iconSet" => "full-symbol-black",
              "icons" => array(array(
                "type" => "socialIcon",
                "iconType" => "facebook",
                "link" => "",
                "image" => $this->social_icon_url . "/07-full-symbol-black/Facebook.png",
                "height" => "32px",
                "width" => "32px",
                "text" => "Facebook"
              ), array(
                "type" => "socialIcon",
                "iconType" => "twitter",
                "link" => "",
                "image" => $this->social_icon_url . "/07-full-symbol-black/Twitter.png",
                "height" => "32px",
                "width" => "32px",
                "text" => "Twitter"
              ), array(
                "type" => "socialIcon",
                "iconType" => "instagram",
                "link" => "",
                "image" => $this->social_icon_url . "/07-full-symbol-black/Instagram.png",
                "height" => "32px",
                "width" => "32px",
                "text" => "Instagram"
              ))
            ), array(
              "type" => "header",
              "text" => "<p><a href=\"[link:newsletter_view_in_browser_url]\">View this email in your web browser</a></p>",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent"
                ),
                "text" => array(
                  "fontColor" => "#222222",
                  "fontFamily" => "Verdana",
                  "fontSize" => "12px",
                  "textAlign" => "center"
                ),
                "link" => array(
                  "fontColor" => "#d83b3b",
                  "textDecoration" => "underline"
                )
              )
            ), array(
              "type" => "footer",
              "text" => "<p><a href=\"[link:subscription_unsubscribe_url]\">Unsubscribe</a> | <a href=\"[link:subscription_manage_url]\">Manage subscription</a><br />Address: Colorado</p>",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent"
                ),
                "text" => array(
                  "fontColor" => "#222222",
                  "fontFamily" => "Verdana",
                  "fontSize" => "12px",
                  "textAlign" => "center"
                ),
                "link" => array(
                  "fontColor" => "#d83b3b",
                  "textDecoration" => "none"
                )
              )
            ))
          ))
        ))
      ),
      "globalStyles" => array(
        "text" => array(
          "fontColor" => "#434343",
          "fontFamily" => "Tahoma",
          "fontSize" => "16px"
        ),
        "h1" => array(
          "fontColor" => "#222222",
          "fontFamily" => "Verdana",
          "fontSize" => "24px"
        ),
        "h2" => array(
          "fontColor" => "#222222",
          "fontFamily" => "Verdana",
          "fontSize" => "22px"
        ),
        "h3" => array(
          "fontColor" => "#222222",
          "fontFamily" => "Verdana",
          "fontSize" => "20px"
        ),
        "link" => array(
          "fontColor" => "#21759B",
          "textDecoration" => "underline"
        ),
        "wrapper" => array(
          "backgroundColor" => "#f0f0f0"
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
