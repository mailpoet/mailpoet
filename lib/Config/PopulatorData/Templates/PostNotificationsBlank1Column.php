<?php
namespace MailPoet\Config\PopulatorData\Templates;

if(!defined('ABSPATH')) exit;

class PostNotificationsBlank1Column {

  function __construct($assets_url) {
    $this->assets_url = $assets_url;
    $this->external_template_image_url = '//ps.w.org/mailpoet/assets/newsletter-templates/post-notifications-blank-1-column';
    $this->template_image_url = $this->assets_url . '/img/blank_templates';
    $this->social_icon_url = $this->assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Post Notifications: Blank 1 Column", 'mailpoet'),
      'description' => __("A blank Post Notifications template with a 1 column layout.", 'mailpoet'),
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
                    "text" => __("Display problems? <a href=\"[link:newsletter_view_in_browser_url]\">Open this email in your web browser.</a>", 'mailpoet'),
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
                    "alt" => "fake-logo",
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
                    "text" => __("<h1 style=\"text-align: center;\"><strong>Check Out Our New Blog Posts! </strong></h1>\n<p></p>\n<p>MailPoet can <span style=\"line-height: 1.6em; background-color: inherit;\"><em>automatically</em> </span><span style=\"line-height: 1.6em; background-color: inherit;\">send your new blog posts to your subscribers.</span></p>\n<p><span style=\"line-height: 1.6em; background-color: inherit;\"></span></p>\n<p><span style=\"line-height: 1.6em; background-color: inherit;\">Below, you'll find three recent posts, which are displayed automatically, thanks to the <em>Automatic Latest Content</em> widget, which can be found in the right sidebar, under <em>Content</em>.</span></p>\n<p><span style=\"line-height: 1.6em; background-color: inherit;\"></span></p>\n<p><span style=\"line-height: 1.6em; background-color: inherit;\">To edit the settings and styles of your post, simply click on a post below.</span></p>", 'mailpoet')
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
                    "type" => "spacer",
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "transparent",
                        "height" => "40px"
                      )
                    )
                  ),
                  array(
                    "type" => "automatedLatestContent",
                    "amount" => "3",
                    "contentType" => "post",
                    "terms" => array(),
                    "inclusionType" => "include",
                    "displayType" => "excerpt",
                    "titleFormat" => "h3",
                    "titleAlignment" => "left",
                    "titleIsLink" => false,
                    "imageFullWidth" => false,
                    "featuredImagePosition" => "belowTitle",
                    "showAuthor" => "no",
                    "authorPrecededBy" => __("Author:", 'mailpoet'),
                    "showCategories" => "no",
                    "categoriesPrecededBy" => __("Categories:", 'mailpoet'),
                    "readMoreType" => "button",
                    "readMoreText" => "Read more",
                    "readMoreButton" => array(
                      "type" => "button",
                      "text" => __("Read the post", 'mailpoet'),
                      "url" => "[postLink]",
                      "styles" => array(
                        "block" => array(
                          "backgroundColor" => "#2ea1cd",
                          "borderColor" => "#0074a2",
                          "borderWidth" => "1px",
                          "borderRadius" => "5px",
                          "borderStyle" => "solid",
                          "width" => "160px",
                          "lineHeight" => "30px",
                          "fontColor" => "#ffffff",
                          "fontFamily" => "Verdana",
                          "fontSize" => "16px",
                          "fontWeight" => "normal",
                          "textAlign" => "center"
                        )
                      )
                    ),
                    "sortBy" => "newest",
                    "showDivider" => true,
                    "divider" => array(
                      "type" => "divider",
                      "styles" => array(
                        "block" => array(
                          "backgroundColor" => "transparent",
                          "padding" => "13px",
                          "borderStyle" => "solid",
                          "borderWidth" => "3px",
                          "borderColor" => "#aaaaaa"
                        )
                      )
                    ),
                    "backgroundColor" => "#ffffff",
                    "backgroundColorAlternate" => "#eeeeee"
                  ),
                  array(
                    "type" => "spacer",
                    "styles" => array(
                      "block" => array(
                        "backgroundColor" => "transparent",
                        "height" => "40px"
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
                    "text" => __("<p><a href=\"[link:subscription_unsubscribe_url]\">Unsubscribe</a> | <a href=\"[link:subscription_manage_url]\">Manage your subscription</a><br />Add your postal address here!</p>", 'mailpoet'),
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
