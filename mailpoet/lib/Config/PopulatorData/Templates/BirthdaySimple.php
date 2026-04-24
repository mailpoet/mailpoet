<?php declare(strict_types = 1);

namespace MailPoet\Config\PopulatorData\Templates;

class BirthdaySimple {

  private $template_image_url;
  private $social_icon_url;

  public function __construct(
    $assets_url
  ) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/birthday-simple';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  public function get() {
    return [
      'name' => __("Birthday Simple", 'mailpoet'),
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
                'backgroundColor' => '#ffffff',
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
                    'type' => 'spacer',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => 'transparent',
                        'height' => '40px',
                      ],
                    ],
                  ],
                  [
                    'type' => 'text',
                    'text' => '<h2 style="text-align: center;"><span style="color: #7c4dff;">' . __("Happy Birthday!", 'mailpoet') . '</span></h2>',
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
                    'text' => '<p style="text-align: center;">' . __("Dear [subscriber:firstname | default:friend], wishing you a wonderful birthday filled with joy and happiness!", 'mailpoet') . '</p>',
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
                    'type' => 'text',
                    'text' => '<p style="text-align: center;">' . __("As a birthday treat, enjoy a special offer on us.", 'mailpoet') . '</p>',
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
                    'text' => __("Get Your Birthday Surprise", 'mailpoet'),
                    'url' => '',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => '#7c4dff',
                        'borderColor' => '#6a3de8',
                        'borderWidth' => '0px',
                        'borderRadius' => '5px',
                        'borderStyle' => 'solid',
                        'width' => '260px',
                        'lineHeight' => '45px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Arial',
                        'fontSize' => '16px',
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
                    'type' => 'footer',
                    'text' => '<p><a href="[link:subscription_unsubscribe_url]">' . __("Unsubscribe", 'mailpoet') . '</a> | <a href="[link:subscription_manage_url]">' . __("Manage your subscription", 'mailpoet') . '</a><br />' . __("Add your postal address here!", 'mailpoet') . '</p>',
                    'styles' => [
                      'block' => [
                        'backgroundColor' => 'transparent',
                      ],
                      'text' => [
                        'fontColor' => '#999999',
                        'fontFamily' => 'Arial',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ],
                      'link' => [
                        'fontColor' => '#7c4dff',
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
          'fontColor' => '#333333',
          'fontFamily' => 'Arial',
          'fontSize' => '15px',
        ],
        'h1' => [
          'fontColor' => '#7c4dff',
          'fontFamily' => 'Arial',
          'fontSize' => '30px',
        ],
        'h2' => [
          'fontColor' => '#7c4dff',
          'fontFamily' => 'Arial',
          'fontSize' => '24px',
        ],
        'h3' => [
          'fontColor' => '#7c4dff',
          'fontFamily' => 'Arial',
          'fontSize' => '22px',
        ],
        'link' => [
          'fontColor' => '#7c4dff',
          'textDecoration' => 'underline',
        ],
        'wrapper' => [
          'backgroundColor' => '#f5f5f5',
        ],
        'body' => [
          'backgroundColor' => '#f5f5f5',
        ],
      ],
    ];
  }

  private function getThumbnail() {
    return $this->template_image_url . '/thumbnail.jpg';
  }
}
