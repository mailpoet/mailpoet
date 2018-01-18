<?php

namespace MailPoet\Config\PopulatorData\Templates;

class ChocolateStore {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
     $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/chocolate_store';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Chocolate Store", 'mailpoet'),
      'description' => __("A classy black store template.", 'mailpoet'),
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
                        'height' => '28px',
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
                    'src' => $this->template_image_url . '/cafe-cocoa-logo_small.png',
                    'alt' => 'cafe-cocoa-logo_small',
                    'fullWidth' => true,
                    'width' => '648px',
                    'height' => '80px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
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
                    'type' => 'social',
                    'iconSet' => 'full-symbol-grey',
                    'icons' => array(
                      0 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'pinterest',
                        'link' => 'http://www.pinterest.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Pinterest.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Pinterest',
                      ),
                      3 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'instagram',
                        'link' => 'http://instagram.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Instagram.png',
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
                        'backgroundColor' => 'transparent',
                        'height' => '36px',
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
                'backgroundColor' => '#5b5b5b',
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
                    'src' => $this->template_image_url . '/cocoa-hero.jpg',
                    'alt' => 'cocoa-hero',
                    'fullWidth' => true,
                    'width' => '1320px',
                    'height' => '677px',
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
                        'backgroundColor' => 'transparent',
                        'height' => '40px',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;">SEASONAL SPECIAL: RUM TRUFFLE</h1>
                       <p style="text-align: left;">Vestibulum eu nulla quis nulla rutrum efficitur ac in orci. Praesent vulputate neque et scelerisque porttitor. Duis mauris ipsum, sagittis nec semper et, dapibus eget nunc. Fusce ornare eros non mauris tempus varius.</p>',
                  ),
                  3 => array(
                    'type' => 'button',
                    'text' => 'Shop Truffles',
                    'url' => '',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#a4a4a4',
                        'borderColor' => '#4e4e4e',
                        'borderWidth' => '2px',
                        'borderRadius' => '5px',
                        'borderStyle' => 'solid',
                        'width' => '180px',
                        'lineHeight' => '40px',
                        'fontColor' => '#000000',
                        'fontFamily' => 'Arial',
                        'fontSize' => '18px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  4 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/bottom-divider-1.png',
                    'alt' => 'bottom-divider',
                    'fullWidth' => true,
                    'width' => '1320px',
                    'height' => '102px',
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
                        'backgroundColor' => 'transparent',
                        'height' => '50px',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'text',
                    'text' => '<h2 style="text-align: center;">Store News</h2>',
                  ),
                  3 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
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
                    'src' => $this->template_image_url . '/cake-shop.png',
                    'alt' => 'cake-shop',
                    'fullWidth' => true,
                    'width' => '300px',
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
                    'src' => $this->template_image_url . '/bottom-divider_lightgrey-1.png',
                    'alt' => 'bottom-divider_lightgrey',
                    'fullWidth' => true,
                    'width' => '440px',
                    'height' => '60px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'header',
                    'text' => '<p><span style="font-family: \'Open Sans\', Arial, sans-serif;"><span style="font-size: 14px;">Duis pellentesque nibh in lectus blandit.</span></span></p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Arial',
                        'fontSize' => '14px',
                        'textAlign' => 'left',
                      ),
                      'link' => array(
                        'fontColor' => '#ffffff',
                        'textDecoration' => 'underline',
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
                    'src' => $this->template_image_url . '/new-desserts.png',
                    'alt' => 'new-desserts',
                    'fullWidth' => true,
                    'width' => '300px',
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
                    'src' => $this->template_image_url . '/bottom-divider_lightgrey-1.png',
                    'alt' => 'bottom-divider_lightgrey',
                    'fullWidth' => true,
                    'width' => '440px',
                    'height' => '60px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'header',
                    'text' => '<p>Phasellus feugiat laoreet ex ac elementum.</p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Arial',
                        'fontSize' => '14px',
                        'textAlign' => 'left',
                      ),
                      'link' => array(
                        'fontColor' => '#a4a4a4',
                        'textDecoration' => 'underline',
                      ),
                    ),
                  ),
                ),
              ),
              2 => array(
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
                    'src' => $this->template_image_url . '/cupcakes.jpg',
                    'alt' => 'cupcakes',
                    'fullWidth' => true,
                    'width' => '300px',
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
                    'src' => $this->template_image_url . '/bottom-divider_lightgrey-1.png',
                    'alt' => 'bottom-divider_lightgrey',
                    'fullWidth' => true,
                    'width' => '440px',
                    'height' => '60px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'header',
                    'text' => '<p>Mauris lacinia venenatis luctus.&nbsp;</p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Arial',
                        'fontSize' => '14px',
                        'textAlign' => 'left',
                      ),
                      'link' => array(
                        'fontColor' => '#ffffff',
                        'textDecoration' => 'underline',
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
                        'height' => '40px',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'button',
                    'text' => 'Visit Store',
                    'url' => '',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => '#a4a4a4',
                        'borderColor' => '#4e4e4e',
                        'borderWidth' => '2px',
                        'borderRadius' => '5px',
                        'borderStyle' => 'solid',
                        'width' => '180px',
                        'lineHeight' => '40px',
                        'fontColor' => '#000000',
                        'fontFamily' => 'Arial',
                        'fontSize' => '20px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '13px',
                        'borderStyle' => 'double',
                        'borderWidth' => '7px',
                        'borderColor' => '#4e4e4e',
                      ),
                    ),
                  ),
                  3 => array(
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;">Cocoa Blogs...</h1>',
                  ),
                  4 => array(
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '8.5px',
                        'borderStyle' => 'double',
                        'borderWidth' => '7px',
                        'borderColor' => '#4e4e4e',
                      ),
                    ),
                  ),
                  5 => array(
                    'type' => 'automatedLatestContent',
                    'amount' => '1',
                    'contentType' => 'post',
                    'terms' => array(),
                    'inclusionType' => 'include',
                    'displayType' => 'excerpt',
                    'titleFormat' => 'h1',
                    'titleAlignment' => 'center',
                    'titleIsLink' => false,
                    'imageFullWidth' => false,
                    'featuredImagePosition' => 'belowTitle',
                    'showAuthor' => 'belowText',
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
                          'backgroundColor' => '#a4a4a4',
                          'borderColor' => '#4e4e4e',
                          'borderWidth' => '2px',
                          'borderRadius' => '5px',
                          'borderStyle' => 'solid',
                          'width' => '180px',
                          'lineHeight' => '40px',
                          'fontColor' => '#000001',
                          'fontFamily' => 'Georgia',
                          'fontSize' => '20px',
                          'fontWeight' => 'normal',
                          'textAlign' => 'center',
                        ),
                      ),
                    ),
                    'sortBy' => 'newest',
                    'showDivider' => false,
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
                  6 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  7 => array(
                    'type' => 'divider',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'padding' => '10.5px',
                        'borderStyle' => 'double',
                        'borderWidth' => '7px',
                        'borderColor' => '#4e4e4e',
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
                    'type' => 'social',
                    'iconSet' => 'full-symbol-grey',
                    'icons' => array(
                      0 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => array(
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/08-full-symbol-grey/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'text',
                    'text' => '<p>Add your postal address here!</p>',
                  ),
                  2 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
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
                    'type' => 'footer',
                    'text' => '<p><em>Praesent metus ante, venenatis egestas nisl ac, molestie viverra ante.&nbsp;</em></p>
                      <p><em>&nbsp;</em></p>
                      <p><a href="[link:subscription_unsubscribe_url]">Unsubscribe<br /></a><a href="[link:subscription_manage_url]">Manage subscription</a></p>',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => array(
                        'fontColor' => '#dadada',
                        'fontFamily' => 'Arial',
                        'fontSize' => '14px',
                        'textAlign' => 'center',
                      ),
                      'link' => array(
                        'fontColor' => '#dadada',
                        'textDecoration' => 'underline',
                      ),
                    ),
                  ),
                  1 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
              2 => array(
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
                    'text' => '<p><em>Aliquam feugiat nisl eget eleifend congue.</em></p>',
                  ),
                  1 => array(
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/owner-1.jpg',
                    'alt' => 'owner',
                    'fullWidth' => false,
                    'width' => '100px',
                    'height' => '100px',
                    'styles' => array(
                      'block' => array(
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  2 => array(
                    'type' => 'spacer',
                    'styles' => array(
                      'block' => array(
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
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
          'fontColor' => '#ffffff',
          'fontFamily' => 'Arial',
          'fontSize' => '16px',
        ),
        'h1' => array(
          'fontColor' => '#ffffff',
          'fontFamily' => 'Georgia',
          'fontSize' => '24px',
        ),
        'h2' => array(
          'fontColor' => '#ffffff',
          'fontFamily' => 'Georgia',
          'fontSize' => '32px',
        ),
        'h3' => array(
          'fontColor' => '#ffffff',
          'fontFamily' => 'Georgia',
          'fontSize' => '24px',
        ),
        'link' => array(
          'fontColor' => '#ffffff',
          'textDecoration' => 'underline',
        ),
        'wrapper' => array(
          'backgroundColor' => '#000000',
        ),
        'body' => array(
          'backgroundColor' => '#000000',
        ),
      ),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/chocolate-store.jpg';
  }

}