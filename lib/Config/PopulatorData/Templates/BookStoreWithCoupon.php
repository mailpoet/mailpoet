<?php

namespace MailPoet\Config\PopulatorData\Templates;

class BookStoreWithCoupon {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/book-store-with-coupon';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Book store (with coupon)", 'mailpoet'),
      'categories' => json_encode(array('woocommerce', 'all')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/thumbnail.jpg';
  }

  private function getBody() {
    return array (
      'content' => 
      array (
        'type' => 'container',
        'columnLayout' => false,
        'orientation' => 'vertical',
        'image' => 
        array (
          'src' => NULL,
          'display' => 'scale',
        ),
        'styles' => 
        array (
          'block' => 
          array (
            'backgroundColor' => 'transparent',
          ),
        ),
        'blocks' => 
        array (
          0 => 
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' => 
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => '#125674',
              ),
            ),
            'blocks' => 
            array (
              0 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'spacer',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '60px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Book-Logo.png',
                    'alt' => 'Book-Logo',
                    'fullWidth' => false,
                    'width' => '200px',
                    'height' => '48px',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  2 => 
                  array (
                    'type' => 'spacer',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          1 => 
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' => 
            array (
              'src' => $this->template_image_url . '/Book-Header.jpg',
              'display' => 'scale',
            ),
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => '#125674',
              ),
            ),
            'blocks' => 
            array (
              0 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'spacer',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '100px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'text',
                    'text' => '<h1 style="text-align: center;"><strong><span style="color: #ffffff;">Turn more pages this weekend...</span></strong></h1>
    <p style="text-align: center;"><span style="color: #ffffff;">Just to say thanks, here\'s a small gift from us to you.</span></p>',
                  ),
                  2 => 
                  array (
                    'type' => 'spacer',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '71px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          2 => 
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' => 
            array (
              'src' => $this->template_image_url . '/Book-Body-2.png',
              'display' => 'tile',
            ),
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => 
            array (
              0 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'spacer',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
              1 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'spacer',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '21px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: center;"><span style="color: #5094ad;"><strong>Read more every week</strong></span></p>
    <h2 style="text-align: center;"><strong>Your free gift!</strong></h2>',
                  ),
                ),
              ),
              2 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'spacer',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          3 => 
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' => 
            array (
              'src' => $this->template_image_url . '/Book-Body-2.png',
              'display' => 'tile',
            ),
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => 
            array (
              0 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'divider',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'padding' => '2px',
                        'borderStyle' => 'dashed',
                        'borderWidth' => '2px',
                        'borderColor' => '#125674',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          4 => 
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' => 
            array (
              'src' => $this->template_image_url . '/Book-Body-2.png',
              'display' => 'tile',
            ),
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => 'transparent',
              ),
            ),
            'blocks' => 
            array (
              0 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'spacer',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: center;"><span>Lorem ipsum dolor sit amet, adipiscing elit. Fusce mollis orci justo,</span></p>
    <p style="text-align: center;"><span>commodo mattis nisi vitae. Sed aliquam, ex ac lacinia tempus,</span></p>
    <p style="text-align: center;"><span>enim urna luctus odio, at leo ante non.</span></p>',
                  ),
                  2 => 
                  array (
                    'type' => 'text',
                    'text' => '<h2 style="text-align: center;"><strong>20% off all books</strong></h2>',
                  ),
                  3 => 
                  array (
                    'type' => 'button',
                    'text' => 'Coupon_Code',
                    'url' => '',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => '#ffffff',
                        'borderColor' => '#125674',
                        'borderWidth' => '3px',
                        'borderRadius' => '40px',
                        'borderStyle' => 'solid',
                        'width' => '288px',
                        'lineHeight' => '50px',
                        'fontColor' => '#125674',
                        'fontFamily' => 'Courier New',
                        'fontSize' => '26px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  4 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Book-Image-Wide-2.png',
                    'alt' => 'Book-Image-Wide',
                    'fullWidth' => true,
                    'width' => '1200px',
                    'height' => '400px',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          5 => 
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' => 
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => '#cdc391',
              ),
            ),
            'blocks' => 
            array (
              0 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'spacer',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          6 => 
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' => 
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => '#cdc391',
              ),
            ),
            'blocks' => 
            array (
              0 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'social',
                    'iconSet' => 'full-symbol-black',
                    'icons' => 
                    array (
                      0 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url.'/07-full-symbol-black/Facebook.png?mailpoet_version=3.16.3',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                    ),
                  ),
                ),
              ),
              1 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'social',
                    'iconSet' => 'full-symbol-black',
                    'icons' => 
                    array (
                      0 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url.'/07-full-symbol-black/Twitter.png?mailpoet_version=3.16.3',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                    ),
                  ),
                ),
              ),
              2 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'social',
                    'iconSet' => 'full-symbol-black',
                    'icons' => 
                    array (
                      0 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'instagram',
                        'link' => 'http://instagram.com',
                        'image' => $this->social_icon_url.'/07-full-symbol-black/Instagram.png?mailpoet_version=3.16.3',
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
          7 => 
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' => 
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => '#125674',
              ),
            ),
            'blocks' => 
            array (
              0 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'spacer',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '29px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          8 => 
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' => 
            array (
              'src' => NULL,
              'display' => 'scale',
            ),
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => '#125674',
              ),
            ),
            'blocks' => 
            array (
              0 => 
              array (
                'type' => 'container',
                'columnLayout' => false,
                'orientation' => 'vertical',
                'image' => 
                array (
                  'src' => NULL,
                  'display' => 'scale',
                ),
                'styles' => 
                array (
                  'block' => 
                  array (
                    'backgroundColor' => 'transparent',
                  ),
                ),
                'blocks' => 
                array (
                  0 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Book-Logo.png',
                    'alt' => 'Book-Logo',
                    'fullWidth' => false,
                    'width' => '96px',
                    'height' => '48px',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'footer',
                    'text' => '<p><span style="color: #cdc391;"><a href="[link:subscription_unsubscribe_url]" style="color: #cdc391;">Unsubscribe</a> | <a href="[link:subscription_manage_url]" style="color: #cdc391;">Manage subscription</a></span><br /><span style="color: #ffffff;">Add your postal address here!</span></p>',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => 
                      array (
                        'fontColor' => '#222222',
                        'fontFamily' => 'Arial',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                      'link' => 
                      array (
                        'fontColor' => '#6cb7d4',
                        'textDecoration' => 'none',
                      ),
                    ),
                  ),
                  2 => 
                  array (
                    'type' => 'header',
                    'text' => '<p><span style="color: #ffffff;"><a href="[link:newsletter_view_in_browser_url]" style="color: #ffffff;">Open this email in your web browser.</a></span></p>',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                      ),
                      'text' => 
                      array (
                        'fontColor' => '#222222',
                        'fontFamily' => 'Arial',
                        'fontSize' => '12px',
                        'textAlign' => 'center',
                      ),
                      'link' => 
                      array (
                        'fontColor' => '#6cb7d4',
                        'textDecoration' => 'underline',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
      'globalStyles' => 
      array (
        'text' => 
        array (
          'fontColor' => '#000000',
          'fontFamily' => 'Noticia Text',
          'fontSize' => '14px',
        ),
        'h1' => 
        array (
          'fontColor' => '#111111',
          'fontFamily' => 'Playfair Display',
          'fontSize' => '30px',
        ),
        'h2' => 
        array (
          'fontColor' => '#222222',
          'fontFamily' => 'Playfair Display',
          'fontSize' => '22px',
        ),
        'h3' => 
        array (
          'fontColor' => '#333333',
          'fontFamily' => 'Arial',
          'fontSize' => '20px',
        ),
        'link' => 
        array (
          'fontColor' => '#21759B',
          'textDecoration' => 'underline',
        ),
        'wrapper' => 
        array (
          'backgroundColor' => '#ffffff',
        ),
        'body' => 
        array (
          'backgroundColor' => '#125674',
        ),
      ),
      'blockDefaults' => 
      array (
        'automatedLatestContent' => 
        array (
          'amount' => '5',
          'withLayout' => false,
          'contentType' => 'post',
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
          'readMoreButton' => 
          array (
            'text' => 'Read more',
            'url' => '[postLink]',
            'context' => 'automatedLatestContent.readMoreButton',
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => '#2ea1cd',
                'borderColor' => '#0074a2',
                'borderWidth' => '1px',
                'borderRadius' => '5px',
                'borderStyle' => 'solid',
                'width' => '180px',
                'lineHeight' => '40px',
                'fontColor' => '#ffffff',
                'fontFamily' => 'Verdana',
                'fontSize' => '18px',
                'fontWeight' => 'normal',
                'textAlign' => 'center',
              ),
            ),
          ),
          'sortBy' => 'newest',
          'showDivider' => true,
          'divider' => 
          array (
            'context' => 'automatedLatestContent.divider',
            'styles' => 
            array (
              'block' => 
              array (
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
        'automatedLatestContentLayout' => 
        array (
          'amount' => '5',
          'withLayout' => true,
          'contentType' => 'post',
          'inclusionType' => 'include',
          'displayType' => 'excerpt',
          'titleFormat' => 'h1',
          'titleAlignment' => 'left',
          'titleIsLink' => false,
          'imageFullWidth' => false,
          'featuredImagePosition' => 'alternate',
          'showAuthor' => 'no',
          'authorPrecededBy' => 'Author:',
          'showCategories' => 'no',
          'categoriesPrecededBy' => 'Categories:',
          'readMoreType' => 'button',
          'readMoreText' => 'Read more',
          'readMoreButton' => 
          array (
            'text' => 'Read more',
            'url' => '[postLink]',
            'context' => 'automatedLatestContentLayout.readMoreButton',
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => '#2ea1cd',
                'borderColor' => '#0074a2',
                'borderWidth' => '1px',
                'borderRadius' => '5px',
                'borderStyle' => 'solid',
                'width' => '180px',
                'lineHeight' => '40px',
                'fontColor' => '#ffffff',
                'fontFamily' => 'Verdana',
                'fontSize' => '18px',
                'fontWeight' => 'normal',
                'textAlign' => 'center',
              ),
            ),
          ),
          'sortBy' => 'newest',
          'showDivider' => true,
          'divider' => 
          array (
            'context' => 'automatedLatestContentLayout.divider',
            'styles' => 
            array (
              'block' => 
              array (
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
        'button' => 
        array (
          'text' => 'Coupon_Code',
          'url' => '',
          'styles' => 
          array (
            'block' => 
            array (
              'backgroundColor' => '#ffffff',
              'borderColor' => '#125674',
              'borderWidth' => '3px',
              'borderRadius' => '40px',
              'borderStyle' => 'solid',
              'width' => '288px',
              'lineHeight' => '50px',
              'fontColor' => '#125674',
              'fontFamily' => 'Courier New',
              'fontSize' => '26px',
              'fontWeight' => 'bold',
              'textAlign' => 'center',
            ),
          ),
          'type' => 'button',
        ),
        'divider' => 
        array (
          'styles' => 
          array (
            'block' => 
            array (
              'backgroundColor' => 'transparent',
              'padding' => '2px',
              'borderStyle' => 'dashed',
              'borderWidth' => '2px',
              'borderColor' => '#125674',
            ),
          ),
          'type' => 'divider',
        ),
        'footer' => 
        array (
          'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br />Add your postal address here!</p>',
          'styles' => 
          array (
            'block' => 
            array (
              'backgroundColor' => 'transparent',
            ),
            'text' => 
            array (
              'fontColor' => '#222222',
              'fontFamily' => 'Arial',
              'fontSize' => '12px',
              'textAlign' => 'center',
            ),
            'link' => 
            array (
              'fontColor' => '#6cb7d4',
              'textDecoration' => 'none',
            ),
          ),
          'type' => 'footer',
        ),
        'posts' => 
        array (
          'amount' => '10',
          'withLayout' => true,
          'contentType' => 'post',
          'postStatus' => 'publish',
          'inclusionType' => 'include',
          'displayType' => 'excerpt',
          'titleFormat' => 'h1',
          'titleAlignment' => 'left',
          'titleIsLink' => false,
          'imageFullWidth' => false,
          'featuredImagePosition' => 'alternate',
          'showAuthor' => 'no',
          'authorPrecededBy' => 'Author:',
          'showCategories' => 'no',
          'categoriesPrecededBy' => 'Categories:',
          'readMoreType' => 'link',
          'readMoreText' => 'Read more',
          'readMoreButton' => 
          array (
            'text' => 'Read more',
            'url' => '[postLink]',
            'context' => 'posts.readMoreButton',
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => '#2ea1cd',
                'borderColor' => '#0074a2',
                'borderWidth' => '1px',
                'borderRadius' => '5px',
                'borderStyle' => 'solid',
                'width' => '180px',
                'lineHeight' => '40px',
                'fontColor' => '#ffffff',
                'fontFamily' => 'Verdana',
                'fontSize' => '18px',
                'fontWeight' => 'normal',
                'textAlign' => 'center',
              ),
            ),
          ),
          'sortBy' => 'newest',
          'showDivider' => true,
          'divider' => 
          array (
            'context' => 'posts.divider',
            'styles' => 
            array (
              'block' => 
              array (
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
        'social' => 
        array (
          'iconSet' => 'full-symbol-black',
          'icons' => 
          array (
            0 => 
            array (
              'type' => 'socialIcon',
              'iconType' => 'instagram',
              'link' => 'http://instagram.com',
              'image' => $this->social_icon_url . '/07-full-symbol-black/Instagram.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Instagram',
            ),
          ),
          'type' => 'social',
        ),
        'spacer' => 
        array (
          'styles' => 
          array (
            'block' => 
            array (
              'backgroundColor' => 'transparent',
              'height' => '20px',
            ),
          ),
          'type' => 'spacer',
        ),
        'header' => 
        array (
          'text' => 'Display problems?&nbsp;<a href="[link:newsletter_view_in_browser_url]">Open this email in your web browser.</a>',
          'styles' => 
          array (
            'block' => 
            array (
              'backgroundColor' => 'transparent',
            ),
            'text' => 
            array (
              'fontColor' => '#222222',
              'fontFamily' => 'Arial',
              'fontSize' => '12px',
              'textAlign' => 'center',
            ),
            'link' => 
            array (
              'fontColor' => '#6cb7d4',
              'textDecoration' => 'underline',
            ),
          ),
          'type' => 'header',
        ),
      ),
    );
  }

}
