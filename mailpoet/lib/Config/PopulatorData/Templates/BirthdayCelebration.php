<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

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
                'backgroundColor' => '#fff0f5',
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
                    'text' => '<h1 style="text-align: center;"><span style="color: #e6527a;">' . __("Happy Birthday, [subscriber:firstname | default:friend]!", 'mailpoet') . '</span></h1>',
                  ],
                  [
                    'type' => 'text',
                    'text' => '<p style="text-align: center; font-size: 17px;"><span style="color: #555555;">' . __("It\u2019s your special day and we want to celebrate with you!", 'mailpoet') . '</span></p>',
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
                    'text' => '<p style="text-align: center; font-size: 15px;"><span style="color: #555555;">' . __("To make your day even sweeter, here\u2019s a special treat just for you.", 'mailpoet') . '</span></p>',
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
                    'text' => __("Claim Your Birthday Gift", 'mailpoet'),
                    'url' => '',
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
          'backgroundColor' => '#fff0f5',
        ],
        'body' => [
          'backgroundColor' => '#fff0f5',
        ],
      ],
    ];
  }

  private function getThumbnail() {
    return $this->template_image_url . '/thumbnail.jpg';
  }
}
