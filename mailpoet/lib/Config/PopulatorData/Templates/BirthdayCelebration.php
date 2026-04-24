<?php declare(strict_types = 1);

namespace MailPoet\Config\PopulatorData\Templates;

class BirthdayCelebration {

  private $template_image_url;
  private $social_icon_url;

  public function __construct(
    $assets_url
  ) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/birthday-celebration';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  public function get() {
    return [
      'name' => __("Birthday Celebration", 'mailpoet'),
      'categories' => json_encode(['standard', 'all']),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    ];
  }

  private function getBody() {
    return [
      'content' => [
        'type' => 'container',
        'orientation' => 'vertical',
        'styles' => [
          'block' => [
            'backgroundColor' => 'transparent',
          ],
        ],
        'blocks' => [
          [
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => [
              'block' => [
                'backgroundColor' => '#fec8c1',
              ],
            ],
            'blocks' => [
              [
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => [
                  'block' => [
                    'backgroundColor' => 'transparent',
                  ],
                ],
                'blocks' => [
                  [
                    'type' => 'header',
                    'text' => '<p><a href="[link:newsletter_view_in_browser_url]">' . __("View this in your browser.", 'mailpoet') . '</a></p>',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => 'transparent',
                      ],
                      'text' => [
                        'fontColor' => '#888888',
                        'fontFamily' => 'Arial',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ],
                      'link' => [
                        'fontColor' => '#e6527a',
                        'textDecoration' => 'underline',
                      ],
                    ],
                  ],
                  [
                    'type' => 'spacer',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ],
                    ],
                  ],
                  [
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/celebration.jpg',
                    'alt' => __('Birthday celebration', 'mailpoet'),
                    'fullWidth' => true,
                    'width' => '1320px',
                    'height' => '578px',
                    'styles' => [
                      'block' => [
                        'textAlign' => 'center',
                      ],
                    ],
                  ],
                  [
                    'type' => 'spacer',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => 'transparent',
                        'height' => '25px',
                      ],
                    ],
                  ],
                  [
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;"><span style="color: #e6527a;">' . __("Happy Birthday, [subscriber:firstname | default:friend]!", 'mailpoet') . '</span></h1>',
                  ],
                  [
                    'type' => 'text',
                    'text' => '<p style="text-align: center; font-size: 17px;"><span style="color: #555555;">' . __("It's your special day and we want to celebrate with you!", 'mailpoet') . '</span></p>',
                  ],
                  [
                    'type' => 'spacer',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => 'transparent',
                        'height' => '25px',
                      ],
                    ],
                  ],
                  [
                    'type' => 'text',
                    'text' => '<p style="text-align: center; font-size: 15px;"><span style="color: #555555;">' . __("To make your day even sweeter, here's a special treat just for you.", 'mailpoet') . '</span></p>',
                  ],
                  [
                    'type' => 'spacer',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => 'transparent',
                        'height' => '15px',
                      ],
                    ],
                  ],
                  [
                    'type' => 'text',
                    'text' => '<p style="text-align: center;"><span style="font-size: 24px; font-weight: bold; letter-spacing: 3px; background-color: #e6527a; color: #ffffff; padding: 12px 24px; border-radius: 5px;">' . __("BIRTHDAY20", 'mailpoet') . '</span></p>',
                  ],
                  [
                    'type' => 'text',
                    'text' => '<p style="text-align: center; font-size: 13px;"><span style="color: #888888;">' . __("Use this code for 20% off your next order.", 'mailpoet') . '</span></p>',
                  ],
                  [
                    'type' => 'spacer',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ],
                    ],
                  ],
                  [
                    'type' => 'button',
                    'text' => __("Shop Now", 'mailpoet'),
                    'url' => '[site:homepage_url]',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => '#ff6b6b',
                        'borderColor' => '#e55a5a',
                        'borderWidth' => '0px',
                        'borderRadius' => '30px',
                        'borderStyle' => 'solid',
                        'width' => '260px',
                        'lineHeight' => '50px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Georgia',
                        'fontSize' => '18px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                      ],
                    ],
                  ],
                  [
                    'type' => 'spacer',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => 'transparent',
                        'height' => '30px',
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
          [
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => [
              'block' => [
                'backgroundColor' => '#fec8c1',
              ],
            ],
            'blocks' => [
              [
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => [
                  'block' => [
                    'backgroundColor' => 'transparent',
                  ],
                ],
                'blocks' => [
                  [
                    'type' => 'social',
                    'iconSet' => 'official',
                    'icons' => [
                      [
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/11-official/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ],
                      [
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/11-official/X.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ],
                      [
                        'type' => 'socialIcon',
                        'iconType' => 'instagram',
                        'link' => 'http://instagram.com',
                        'image' => $this->social_icon_url . '/11-official/Instagram.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Instagram',
                      ],
                    ],
                  ],
                  [
                    'type' => 'spacer',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ],
                    ],
                  ],
                  [
                    'type' => 'footer',
                    'text' => '<p><a href="[link:subscription_unsubscribe_url]">' . __("Unsubscribe", 'mailpoet') . '</a> | <a href="[link:subscription_manage_url]">' . __("Manage your subscription", 'mailpoet') . '</a><br />' . __("Add your postal address here!", 'mailpoet') . '</p>',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => 'transparent',
                      ],
                      'text' => [
                        'fontColor' => '#888888',
                        'fontFamily' => 'Arial',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ],
                      'link' => [
                        'fontColor' => '#e6527a',
                        'textDecoration' => 'none',
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
      'globalStyles' => [
        'text' => [
          'fontColor' => '#555555',
          'fontFamily' => 'Georgia',
          'fontSize' => '15px',
        ],
        'h1' => [
          'fontColor' => '#e6527a',
          'fontFamily' => 'Georgia',
          'fontSize' => '32px',
        ],
        'h2' => [
          'fontColor' => '#e6527a',
          'fontFamily' => 'Georgia',
          'fontSize' => '24px',
        ],
        'h3' => [
          'fontColor' => '#e6527a',
          'fontFamily' => 'Georgia',
          'fontSize' => '22px',
        ],
        'link' => [
          'fontColor' => '#e6527a',
          'textDecoration' => 'underline',
        ],
        'wrapper' => [
          'backgroundColor' => '#fec8c1',
        ],
        'body' => [
          'backgroundColor' => '#fec8c1',
        ],
      ],
    ];
  }

  private function getThumbnail() {
    return $this->template_image_url . '/thumbnail.jpg';
  }
}
