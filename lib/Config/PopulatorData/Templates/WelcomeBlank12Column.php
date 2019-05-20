<?php
namespace MailPoet\Config\PopulatorData\Templates;

use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;


class WelcomeBlank12Column {

  private $assets_url;
  private $external_template_image_url;
  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->assets_url = $assets_url;
    $this->external_template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/welcome-email-blank-1-2-column';
    $this->template_image_url = $this->assets_url . '/img/blank_templates';
    $this->social_icon_url = $this->assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return [
      'name' => WPFunctions::get()->__("Welcome Email: Blank 1:2 Column", 'mailpoet'),
      'categories' => json_encode(['welcome']),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    ];
  }

  private function getBody() {
    return [
      "content" => [
        "type" => "container",
        "orientation" => "vertical",
        "styles" => [
          "block" => [
            "backgroundColor" => "transparent",
          ],
        ],
        "blocks" => [
          [
            "type" => "container",
            "orientation" => "horizontal",
            "styles" => [
              "block" => [
                "backgroundColor" => "#f8f8f8",
              ],
            ],
            "blocks" => [
              [
                "type" => "container",
                "orientation" => "vertical",
                "styles" => [
                  "block" => [
                    "backgroundColor" => "transparent",
                  ],
                ],
                "blocks" => [
                  [
                    "type" => "header",
                    "text" => WPFunctions::get()->__("Display problems? <a href=\"[link:newsletter_view_in_browser_url]\">Open this email in your web browser.</a>", 'mailpoet'),
                    "styles" => [
                      "block" => [
                        "backgroundColor" => "transparent",
                      ],
                      "text" => [
                        "fontColor" => "#222222",
                        "fontFamily" => "Arial",
                        "fontSize" => "12px",
                        "textAlign" => "center",
                      ],
                      "link" => [
                        "fontColor" => "#6cb7d4",
                        "textDecoration" => "underline",
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
          [
            "type" => "container",
            "orientation" => "horizontal",
            "styles" => [
              "block" => [
                "backgroundColor" => "#ffffff",
              ],
            ],
            "blocks" => [
              [
                "type" => "container",
                "orientation" => "vertical",
                "styles" => [
                  "block" => [
                    "backgroundColor" => "transparent",
                  ],
                ],
                "blocks" => [
                  [
                    "type" => "spacer",
                    "styles" => [
                      "block" => [
                        "backgroundColor" => "transparent",
                        "height" => "30px",
                      ],
                    ],
                  ],
                  [
                    "type" => "image",
                    "link" => "",
                    "src" => $this->template_image_url . "/fake-logo.png",
                    "alt" => WPFunctions::get()->__("Fake logo", 'mailpoet'),
                    "fullWidth" => false,
                    "width" => "598px",
                    "height" => "71px",
                    "styles" => [
                      "block" => [
                        "textAlign" => "center",
                      ],
                    ],
                  ],
                  [
                    "type" => "text",
                    "text" => WPFunctions::get()->__("<h1 style=\"text-align: center;\"><strong>Hi, new subscriber!</strong></h1>\n<p>&nbsp;</p>\n<p>[subscriber:firstname | default:Subscriber],</p>\n<p>&nbsp;</p>\n<p>You recently joined our list and we'd like to give you a warm welcome!</p>", 'mailpoet'),
                  ],
                  [
                    "type" => "divider",
                    "styles" => [
                      "block" => [
                        "backgroundColor" => "transparent",
                        "padding" => "13px",
                        "borderStyle" => "solid",
                        "borderWidth" => "3px",
                        "borderColor" => "#aaaaaa",
                      ],
                    ],
                  ],
                  [
                    "type" => "spacer",
                    "styles" => [
                      "block" => [
                        "backgroundColor" => "transparent",
                        "height" => "20px",
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
          [
            "type" => "container",
            "orientation" => "horizontal",
            "styles" => [
              "block" => [
                "backgroundColor" => "transparent",
              ],
            ],
            "blocks" => [
              [
                "type" => "container",
                "orientation" => "vertical",
                "styles" => [
                  "block" => [
                    "backgroundColor" => "transparent",
                  ],
                ],
                "blocks" => [
                  [
                    "type" => "text",
                    "text" => WPFunctions::get()->__("<h3>Our Most Popular Posts</h3>", 'mailpoet'),
                  ],
                  [
                    "type" => "text",
                    "text" => WPFunctions::get()->__("<ul>\n<li><a href=\"http://www.mailpoet.com/the-importance-of-focus-when-writing/\">The Importance of Focus When Writing</a></li>\n<li><a href=\"http://www.mailpoet.com/write-great-subject-line/\">How to Write a Great Subject Line</a></li>\n<li><a href=\"http://www.mailpoet.com/just-sit-write-advice-motivation-ernest-hemingway/\">Just Sit Down and Write &ndash; Advice on Motivation from Ernest Hemingway</a></li>\n</ul>", 'mailpoet'),
                  ],
                ],
              ],
              [
                "type" => "container",
                "orientation" => "vertical",
                "styles" => [
                  "block" => [
                    "backgroundColor" => "transparent",
                  ],
                ],
                "blocks" => [
                  [
                    "type" => "text",
                    "text" => WPFunctions::get()->__("<h3>What's Next?</h3>", 'mailpoet'),
                  ],
                  [
                    "type" => "text",
                    "text" => WPFunctions::get()->__("<p>Add a single button to your newsletter in order to have one clear call-to-action, which will increase your click rates.</p>", 'mailpoet'),
                  ],
                  [
                    "type" => "button",
                    "text" => WPFunctions::get()->__("Read up!", 'mailpoet'),
                    "url" => "",
                    "styles" => [
                      "block" => [
                        "backgroundColor" => "#2ea1cd",
                        "borderColor" => "#0074a2",
                        "borderWidth" => "1px",
                        "borderRadius" => "5px",
                        "borderStyle" => "solid",
                        "width" => "180px",
                        "lineHeight" => "40px",
                        "fontColor" => "#ffffff",
                        "fontFamily" => "Verdana",
                        "fontSize" => "18px",
                        "fontWeight" => "normal",
                        "textAlign" => "center",
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
          [
            "type" => "container",
            "orientation" => "horizontal",
            "styles" => [
              "block" => [
                "backgroundColor" => "#f8f8f8",
              ],
            ],
            "blocks" => [
              [
                "type" => "container",
                "orientation" => "vertical",
                "styles" => [
                  "block" => [
                    "backgroundColor" => "transparent",
                  ],
                ],
                "blocks" => [
                  [
                    "type" => "divider",
                    "styles" => [
                      "block" => [
                        "backgroundColor" => "transparent",
                        "padding" => "24.5px",
                        "borderStyle" => "solid",
                        "borderWidth" => "3px",
                        "borderColor" => "#aaaaaa",
                      ],
                    ],
                  ],
                  [
                    "type" => "social",
                    "iconSet" => "grey",
                    "icons" => [
                      [
                        "type" => "socialIcon",
                        "iconType" => "facebook",
                        "link" => "http://www.facebook.com",
                        "image" => $this->social_icon_url . "/02-grey/Facebook.png",
                        "height" => "32px",
                        "width" => "32px",
                        "text" => "Facebook",
                      ],
                      [
                        "type" => "socialIcon",
                        "iconType" => "twitter",
                        "link" => "http://www.twitter.com",
                        "image" => $this->social_icon_url . "/02-grey/Twitter.png",
                        "height" => "32px",
                        "width" => "32px",
                        "text" => "Twitter",
                      ],
                    ],
                  ],
                  [
                    "type" => "divider",
                    "styles" => [
                      "block" => [
                        "backgroundColor" => "transparent",
                        "padding" => "7.5px",
                        "borderStyle" => "solid",
                        "borderWidth" => "3px",
                        "borderColor" => "#aaaaaa",
                      ],
                    ],
                  ],
                  [
                    "type" => "footer",
                    "text" => WPFunctions::get()->__("<p><a href=\"[link:subscription_unsubscribe_url]\">Unsubscribe</a> | <a href=\"[link:subscription_manage_url]\">Manage your subscription</a><br />Add your postal address here!</p>", 'mailpoet'),
                    "styles" => [
                      "block" => [
                        "backgroundColor" => "transparent",
                      ],
                      "text" => [
                        "fontColor" => "#222222",
                        "fontFamily" => "Arial",
                        "fontSize" => "12px",
                        "textAlign" => "center",
                      ],
                      "link" => [
                        "fontColor" => "#6cb7d4",
                        "textDecoration" => "none",
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
      "globalStyles" => [
        "text" => [
          "fontColor" => "#000000",
          "fontFamily" => "Arial",
          "fontSize" => "16px",
        ],
        "h1" => [
          "fontColor" => "#111111",
          "fontFamily" => "Trebuchet MS",
          "fontSize" => "26px",
        ],
        "h2" => [
          "fontColor" => "#222222",
          "fontFamily" => "Trebuchet MS",
          "fontSize" => "24px",
        ],
        "h3" => [
          "fontColor" => "#333333",
          "fontFamily" => "Trebuchet MS",
          "fontSize" => "22px",
        ],
        "link" => [
          "fontColor" => "#21759B",
          "textDecoration" => "underline",
        ],
        "wrapper" => [
          "backgroundColor" => "#ffffff",
        ],
        "body" => [
          "backgroundColor" => "#eeeeee",
        ],
      ],
    ];
  }

  private function getThumbnail() {
    return $this->external_template_image_url . '/thumbnail.20190411-1500.jpg';
  }

}
