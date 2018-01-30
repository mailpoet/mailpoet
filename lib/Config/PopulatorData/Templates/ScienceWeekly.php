<?php

namespace MailPoet\Config\PopulatorData\Templates;

class ScienceWeekly {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
     $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/science_weekly';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Science Weekly", 'mailpoet'),
      'description' => __("The right chemistry to send your weekly posts.", 'mailpoet'),
      'categories' => json_encode(array('standard', 'sample')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getBody() {
    return array(
      'content' => array(
        'type' => 'container',
        'orientation' => 'vertical',
        'styles' => array(
          'block' => array(
            'backgroundColor' => 'transparent',
          ),
        ),
        'blocks' => array(
          0 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#ffffff',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Science-Logo.png',
                    'alt' => 'Science-Logo',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '300px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Health-Mag-Title-2.png',
                    'alt' => 'Health-Mag-Title-2',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '300px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          1 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#b1b6d1',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'text',
                    'text' => '<p style="text-align: center; font-size: 12px;"><span style="color: #ffffff;">Display problems?&nbsp;<a href="[link:newsletter_view_in_browser_url]" style="color: #ffffff;">Open this email in your web browser.</a></span></p>',
                  ),
                ),
              ),
            ),
          ),
          2 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#b1b6d1',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          3 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => '#ffffff',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'automatedLatestContent',
                    'amount' => '2',
                    'contentType' => 'post',
                    'terms' => array(),
                    'inclusionType' => 'include',
                    'displayType' => 'excerpt',
                    'titleFormat' => 'h1',
                    'titleAlignment' => 'left',
                    'titleIsLink' => false,
                    'imageFullWidth' => false,
                    'featuredImagePosition' => 'belowTitle',
                    'showAuthor' => 'no',
                    'authorPrecededBy' => 'Author:',
                    'showCategories' => 'no',
                    'categoriesPrecededBy' => 'Categories:',
                    'readMoreType' => 'button',
                    'readMoreText' => 'Read more',
                    'readMoreButton' => array(
                      'type' => 'button',
                      'text' => 'Read more',
                      'url' => '[postLink]',
                      'styles' => array(
                        'block' => array(
                          'backgroundColor' => '#2b2d37',
                          'borderColor' => '#112d31',
                          'borderWidth' => '1px',
                          'borderRadius' => '21px',
                          'borderStyle' => 'solid',
                          'width' => '114px',
                          'lineHeight' => '33px',
                          'fontColor' => '#ffffff',
                          'fontFamily' => 'Arial',
                          'fontSize' => '16px',
                          'fontWeight' => 'normal',
                          'textAlign' => 'left',
                        ),
                      ),
                    ),
                    'sortBy' => 'newest',
                    'showDivider' => true,
                    'divider' => array(
                      'type' => 'divider',
                      'styles' => array(
                        'block' => array(
                          'backgroundColor' => 'transparent',
                          'padding' => '13px',
                          'borderStyle' => 'solid',
                          'borderWidth' => '3px',
                          'borderColor' => '#aaaaaa',
                        ),
                      ),
                    ),
                    'backgroundColor' => '#ffffff',
                    'backgroundColorAlternate' => '#eeeeee',
                  ),
                ),
              ),
            ),
          ),
          4 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#ffffff',
                        'height' => '30px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#b1b6d1',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Health-Mag-End-1.png',
                    'alt' => 'Health-Mag-End',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '50px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  3 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#2b2d37',
                        'height' => '35px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          5 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Health-Mag-Promo-Start.png',
                    'alt' => 'Health-Mag-Promo-Start',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '50px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          6 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '50px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<h2><strong>Download our app!</strong></h2>
                      <p><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed in odio dui. Duis et dolor nec erat dictum laoreet. Morbi dapibus turpis id eros viverra tempor. </span></p>
                      <p><span></span></p>
                      <p><span>Fusce et diam ac sapien posuere luctus. Etiam in vehicula metus, ac viverra elit. Duis diam lacus, molestie vel enim non, rutrum placerat massa. Suspendisse a elit tincidunt, egestas lacus at, maximus diam. </span></p>
                      <p><span></span></p>',
                  ),
                  2 => array(
                    'type' => 'button',
                    'text' => 'Download Now',
                    'url' => '',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#2b2d37',
                        'borderColor' => '#2b2d37',
                        'borderWidth' => '1px',
                        'borderRadius' => '40px',
                        'borderStyle' => 'solid',
                        'width' => '144px',
                        'lineHeight' => '40px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Arial',
                        'fontSize' => '16px',
                        'fontWeight' => 'normal',
                        'textAlign' => 'left',
                      ),
                    ),
                  ),
                ),
              ),
              1 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Health-Mag-Phone.png',
                    'alt' => 'Health-Mag-Phone',
                    'fullWidth' => false,
                    'width' => '400px',
                    'height' => '573px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          7 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Health-Mag-Promo-End.png',
                    'alt' => 'Health-Mag-Promo-End',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '50px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          8 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#2b2d37',
                        'height' => '35px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Health-Mag-Promo-Start.png',
                    'alt' => 'Health-Mag-Promo-Start',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '50px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          9 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'text',
                    'text' => '<h3 style="text-align: center;">Keep In Touch With Us</h3>',
                  ),
                  1 => array(
                    'type' => 'social',
                    'iconSet' => 'circles',
                    'icons' => array(
                      0 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/03-circles/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/03-circles/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'youtube',
                        'link' => 'http://www.youtube.com',
                        'image' => $this->social_icon_url . '/03-circles/Youtube.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Youtube',
                      ),
                      3 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'instagram',
                        'link' => 'http://instagram.com',
                        'image' => $this->social_icon_url . '/03-circles/Instagram.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Instagram',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          10 => array(
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => array(
              'block' => array(
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => array(
              0 => array(
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => array(
                  'block' => array(
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => array(
                  0 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Health-Mag-Promo-End.png',
                    'alt' => 'Health-Mag-Promo-End',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '50px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#2b2d37',
                        'height' => '26px',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'footer',
                    'text' => '<p><span style="color: #b1b6d1;"><a href="[link:subscription_unsubscribe_url]" style="color: #b1b6d1;">Unsubscribe</a> | <a href="[link:subscription_manage_url]" style="color: #b1b6d1;">Manage subscription</a></span><br />Add your postal address here!</p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#2b2d37',
                      ),
                      'text' => array(
                        'fontColor' => '#d6d6d6',
                        'fontFamily' => 'Arial',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                      'link' => array(
                        'fontColor' => '#6cb7d4',
                        'textDecoration' => 'none',
                      ),
                    ),
                  ),
                  3 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#2b2d37',
                        'height' => '40px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
      'globalStyles' => array(
        'text' => array(
          'fontColor' => '#000000',
          'fontFamily' => 'Arial',
          'fontSize' => '15px',
        ),
        'h1' => array(
          'fontColor' => '#111111',
          'fontFamily' => 'Arial',
          'fontSize' => '26px',
        ),
        'h2' => array(
          'fontColor' => '#222222',
          'fontFamily' => 'Arial',
          'fontSize' => '22px',
        ),
        'h3' => array(
          'fontColor' => '#333333',
          'fontFamily' => 'Arial',
          'fontSize' => '20px',
        ),
        'link' => array(
          'fontColor' => '#21759B',
          'textDecoration' => 'underline',
        ),
        'wrapper' => array(
          'backgroundColor' => '#ffffff',
        ),
        'body' => array(
          'backgroundColor' => '#2b2d37',
        ),
      ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/science-weekly.jpg';
  }

}