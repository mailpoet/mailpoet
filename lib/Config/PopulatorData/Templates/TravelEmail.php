<?php
namespace MailPoet\Config\PopulatorData\Templates;

if(!defined('ABSPATH')) exit;

class TravelEmail {

  function __construct($assets_url) {
    $this->assets_url = $assets_url;
    $this->external_template_image_url = '//ps.w.org/mailpoet/assets/newsletter-templates/travel-email';
    $this->template_image_url = $this->assets_url . '/img/sample_templates/travel';
    $this->social_icon_url = $this->assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Travel email", 'mailpoet'),
      'description' => __("A little postcard from your trip", 'mailpoet'),
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
              "src" => $this->external_template_image_url . "/header.png",
              "alt" => __("Travelling Tales with Jane & Steven", 'mailpoet'),
              "fullWidth" => true,
              "width" => "660px",
              "height" => "165px",
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
              "type" => "text",
              "text" => __("<h1 style=\"text-align: center;\">Hi [subscriber:firstname | default:reader]!</h1>\n<p></p>\n<p>Greetings from New Zealand! We're here enjoying the sights, sounds, and smells of Rotarua! Yesterday, we visited the local hot springs, and today, we're going swimming.</p>\n<p>Don't forget to stay updated with Twitter!</p>", 'mailpoet')
            ), array(
              "type" => "social",
              "iconSet" => "circles",
              "icons" => array(array(
                "type" => "socialIcon",
                "iconType" => "twitter",
                "link" => "",
                "image" => $this->social_icon_url . "/03-circles/Twitter.png",
                "height" => "32px",
                "width" => "32px",
                "text" => __("Twitter", 'mailpoet')
              ))
            ), array(
              "type" => "text",
              "text" => __("<h1 style=\"text-align: center;\"><strong>Photos from Rotarua</strong></h1>", 'mailpoet')
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
            "src" => $this->external_template_image_url . "/gallery1.jpg",
              "alt" => __("hot thermals", 'mailpoet'),
              "fullWidth" => false,
              "width" => "640px",
              "height" => "425px",
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
                  "height" => "40px"
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
              "src" => $this->external_template_image_url . "/gallery2.jpg",
              "alt" => __("The view from our campsite", 'mailpoet'),
              "fullWidth" => false,
              "width" => "640px",
              "height" => "425px",
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
                  "height" => "40px"
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
              "src" => $this->external_template_image_url . "/gallery3.jpg",
              "alt" => __("Red sky at night", 'mailpoet'),
              "fullWidth" => false,
              "width" => "640px",
              "height" => "425px",
              "styles" => array(
                "block" => array(
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
              "src" => $this->external_template_image_url . "/gallery4.jpg",
              "alt" => __("Don't go chasing waterfalls", 'mailpoet'),
              "fullWidth" => false,
              "width" => "640px",
              "height" => "425px",
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
              "type" => "spacer",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent",
                  "height" => "20px"
                )
              )
            ), array(
              "type" => "button",
              "text" => __("View NZ Photo Gallery", 'mailpoet'),
              "url" => "",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "#f16161",
                  "borderColor" => "#ffffff",
                  "borderWidth" => "3px",
                  "borderRadius" => "5px",
                  "borderStyle" => "solid",
                  "width" => "288px",
                  "lineHeight" => "48px",
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
              "type" => "divider",
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent",
                  "padding" => "23px",
                  "borderStyle" => "double",
                  "borderWidth" => "3px",
                  "borderColor" => "#aaaaaa"
                )
              )
            ), array(
              "type" => "text",
              "text" => __("<h2><em>Here's our top recommendations in Rotarua</em></h2>", 'mailpoet')
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
              "src" => $this->external_template_image_url . "/glow-worms.jpg",
              "alt" => __("Glowworms, Waitomo Caves", 'mailpoet'),
              "fullWidth" => true,
              "width" => "640px",
              "height" => "428px",
              "styles" => array(
                "block" => array(
                  "textAlign" => "center"
                )
              )
            ), array(
              "type" => "text",
              "text" => __("<p><em><a href=\"http://www.waitomo.com/Waitomo-Glowworm-Caves/Pages/default.aspx\"><strong>Waitomo GlowWorm Caves</strong></a></em></p>", 'mailpoet')
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
              "src" => $this->external_template_image_url . "/luge.jpg",
              "alt" => __("luge", 'mailpoet'),
              "fullWidth" => false,
              "width" => "375px",
              "height" => "500px",
              "styles" => array(
                "block" => array(
                  "textAlign" => "center"
                )
              )
            ), array(
              "type" => "text",
              "text" => __("<p><em><strong><a href=\"http://www.skyline.co.nz/rotorua/ssr_luge/\">Luge!</a></strong></em></p>", 'mailpoet')
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
              "src" => $this->external_template_image_url . "/holiday-park.jpg",
              "alt" => __("holiday-park", 'mailpoet'),
              "fullWidth" => true,
              "width" => "640px",
              "height" => "425px",
              "styles" => array(
                "block" => array(
                  "textAlign" => "center"
                )
              )
            ), array(
              "type" => "text",
              "text" => __("<p><em><strong><a href=\"http://rotoruathermal.co.nz/\">Roturua Thermal Holiday Park</a></strong></em></p>", 'mailpoet')
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
                  "height" => "21px"
                )
              )
            ), array(
              "type" => "text",
              "text" => __("<p>Tomorrow we're heading towards Taupo where we'll visit the 'Craters of the moon' and go prawn fishing! Hopefully the weather will stay good.</p>\n<p></p>\n<p>Keep on travellin'</p>\n<p>Jane &amp; Steven</p>", 'mailpoet')
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
                  "padding" => "13px",
                  "borderStyle" => "dotted",
                  "borderWidth" => "2px",
                  "borderColor" => "#aaaaaa"
                )
              )
            ), array(
              "type" => "header",
              "text" => ("Display problems? <a href=\"[link:newsletter_view_in_browser_url]\">Open this email in your web browser.</a>"),
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent"
                ),
                "text" => array(
                  "fontColor" => "#222222",
                  "fontFamily" => "Courier New",
                  "fontSize" => "12px",
                  "textAlign" => "center"
                ),
                "link" => array(
                  "fontColor" => "#343434",
                  "textDecoration" => "underline"
                )
              )
            ), array(
              "type" => "footer",
              "text" => __("<p><a href=\"[link:subscription_unsubscribe_url]\">Unsubscribe</a> | <a href=\"[link:subscription_manage_url]\">Manage subscription</a></p>", 'mailpoet'),
              "styles" => array(
                "block" => array(
                  "backgroundColor" => "transparent"
                ),
                "text" => array(
                  "fontColor" => "#343434",
                  "fontFamily" => "Courier New",
                  "fontSize" => "12px",
                  "textAlign" => "center"
                ),
                "link" => array(
                  "fontColor" => "#343434",
                  "textDecoration" => "underline"
                )
              )
            ))
          ))
        ))
      ),
      "globalStyles" => array(
        "text" => array(
          "fontColor" => "#343434",
          "fontFamily" => "Courier New",
          "fontSize" => "16px"
        ),
        "h1" => array(
          "fontColor" => "#180d6b",
          "fontFamily" => "Trebuchet MS",
          "fontSize" => "24px"
        ),
        "h2" => array(
          "fontColor" => "#180d6b",
          "fontFamily" => "Trebuchet MS",
          "fontSize" => "20px"
        ),
        "h3" => array(
          "fontColor" => "#343434",
          "fontFamily" => "Trebuchet MS",
          "fontSize" => "18px"
        ),
        "link" => array(
          "fontColor" => "#f16161",
          "textDecoration" => "underline"
        ),
        "wrapper" => array(
          "backgroundColor" => "#daf3ff"
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
