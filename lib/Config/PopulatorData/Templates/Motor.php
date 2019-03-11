<?php

namespace MailPoet\Config\PopulatorData\Templates;

class Motor {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/motor';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("Motor", 'mailpoet'),
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
                'backgroundColor' => '#e3e3e3',
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
          1 =>
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
                'backgroundColor' => '#e3e3e3',
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
                        'height' => '24px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'social',
                    'iconSet' => 'full-symbol-color',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'textAlign' => 'center',
                      ),
                    ),
                    'icons' =>
                    array (
                      0 =>
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Facebook.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 =>
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Twitter.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 =>
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'youtube',
                        'link' => 'http://www.youtube.com',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Youtube.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Youtube',
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
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: center;"><span style="color: #d52a2a;"><strong>Welcome to Vector Motors</strong></span></p>
    <p style="text-align: center; font-size: 11px;"><span style="color: #d52a2a;"><a href="[link:newsletter_view_in_browser_url]">View email in browser &gt;</a></span></p>',
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
                        'height' => '24px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'social',
                    'iconSet' => 'full-symbol-color',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'textAlign' => 'center',
                      ),
                    ),
                    'icons' =>
                    array (
                      0 =>
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'instagram',
                        'link' => 'http://instagram.com',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Instagram.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Instagram',
                      ),
                      1 =>
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'email',
                        'link' => '',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Email.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Email',
                      ),
                      2 =>
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'website',
                        'link' => '',
                        'image' => $this->social_icon_url . '/06-full-symbol-color/Website.png',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Website',
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
            'columnLayout' => '2_1',
            'orientation' => 'horizontal',
            'image' =>
            array (
              'src' => $this->template_image_url . '/Car-Header-2.jpg',
              'display' => 'scale',
            ),
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => '#d52a2a',
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
                    'src' => $this->template_image_url . '/Car-Header-logo-1.png',
                    'alt' => 'Car-Header-logo-1',
                    'fullWidth' => false,
                    'width' => '167.5px',
                    'height' => '349px',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'textAlign' => 'left',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'spacer',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '118px',
                      ),
                    ),
                  ),
                  2 =>
                  array (
                    'type' => 'text',
                    'text' => '<h3 style="font-size: 35px; line-height: 40px; text-align: left; border-left: 10px solid #d52a2a; margin-left: 20px; padding-left: 20px;"><strong><span style="color: #ffffff;">Welcome to the club</span></strong></h3>',
                  ),
                  3 =>
                  array (
                    'type' => 'spacer',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '72px',
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
              'src' => NULL,
              'display' => 'scale',
            ),
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => '#ffffff',
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
                        'backgroundColor' => '#ffffff',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<h2 style="text-align: center;"><strong><span style="color: #d52a2a;">You\'ve bought the car,&nbsp;here\'s&nbsp;what happens next.</span></strong></h2>
    <p style="text-align: center;"><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam a elementum ex. Aliquam mollis metus ac nisl luctus pulvinar. Donec tincidunt pharetra sem, nec eleifend augue. Morbi id nunc commodo, tempor erat et, pretium neque. </span></p>
    <p style="text-align: center;"><span></span></p>
    <p style="text-align: center;"><span>Vivamus ante sapien, consequat vitae ante quis, facilisis pellentesque mi. Praesent at scelerisque leo. Donec elementum mi consequat, ultrices lorem nec, vestibulum arcu. Aenean id libero vitae felis consequat maximus.</span></p>',
                  ),
                  2 =>
                  array (
                    'type' => 'spacer',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#e3e3e3',
                        'height' => '40px',
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
              'src' => NULL,
              'display' => 'scale',
            ),
            'styles' =>
            array (
              'block' =>
              array (
                'backgroundColor' => '#e3e3e3',
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
                    'type' => 'text',
                    'text' => '<h1><strong>We\'re here to help</strong></h1>
    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam a elementum ex. Aliquam mollis metus ac nisl luctus pulvinar. Donec tincidunt pharetra sem, nec eleifend augue. Morbi id nunc commodo, tempor erat et, pretium neque.</p>
    <p></p>
    <p><strong><a href="">Get in touch with us here</a></strong></p>',
                  ),
                  1 =>
                  array (
                    'type' => 'spacer',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#e3e3e3',
                        'height' => '113px',
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
                        'backgroundColor' => '#ffffff',
                        'height' => '42px',
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
                    'type' => 'button',
                    'text' => 'Servicing Plans',
                    'url' => '',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#d52a2a',
                        'borderColor' => '#0074a2',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '176px',
                        'lineHeight' => '50px',
                        'fontColor' => '#ffffff',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '18px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'spacer',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#e3e3e3',
                        'height' => '30px',
                      ),
                    ),
                  ),
                  2 =>
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Car-Wheel.jpg',
                    'alt' => 'Car-Wheel',
                    'fullWidth' => true,
                    'width' => '600px',
                    'height' => '560px',
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
                        'backgroundColor' => '#ffffff',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'text',
                    'text' => '<h3 style="text-align: center;"><strong>We\'ll be in touch soon with updates</strong></h3>
    <p style="text-align: center;"><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam a elementum ex. Aliquam mollis metus ac nisl luctus pulvinar. Donec tincidunt pharetra sem, nec eleifend augue. Morbi id nunc commodo, tempor erat et, pretium neque.</span></p>',
                  ),
                  2 =>
                  array (
                    'type' => 'spacer',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#ffffff',
                        'height' => '35px',
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
                'backgroundColor' => '#e3e3e3',
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
                        'backgroundColor' => '#e3e3e3',
                        'height' => '20px',
                      ),
                    ),
                  ),
                  1 =>
                  array (
                    'type' => 'footer',
                    'text' => '<p><span style="color: #d52a2a;"><a href="[link:subscription_unsubscribe_url]" style="color: #d52a2a;">Unsubscribe</a> | <a href="[link:subscription_manage_url]" style="color: #d52a2a;">Manage your subscription</a></span><br />Add your postal address here!</p>',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Car-Footer.jpg',
                    'alt' => 'Car-Footer',
                    'fullWidth' => true,
                    'width' => '1280px',
                    'height' => '275px',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  3 =>
                  array (
                    'type' => 'spacer',
                    'styles' =>
                    array (
                      'block' =>
                      array (
                        'backgroundColor' => '#d52a2a',
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
      'globalStyles' =>
      array (
        'text' =>
        array (
          'fontColor' => '#000000',
          'fontFamily' => 'Roboto',
          'fontSize' => '14px',
        ),
        'h1' =>
        array (
          'fontColor' => '#111111',
          'fontFamily' => 'Merriweather Sans',
          'fontSize' => '30px',
        ),
        'h2' =>
        array (
          'fontColor' => '#222222',
          'fontFamily' => 'Merriweather Sans',
          'fontSize' => '24px',
        ),
        'h3' =>
        array (
          'fontColor' => '#333333',
          'fontFamily' => 'Merriweather Sans',
          'fontSize' => '22px',
        ),
        'link' =>
        array (
          'fontColor' => '#d52a2a',
          'textDecoration' => 'underline',
        ),
        'wrapper' =>
        array (
          'backgroundColor' => '#ffffff',
        ),
        'body' =>
        array (
          'backgroundColor' => '#e3e3e3',
        ),
      ),
      'blockDefaults' =>
      array (
        'automatedLatestContent' =>
        array (
          'amount' => '5',
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
          'text' => 'Servicing Plans',
          'url' => '',
          'styles' =>
          array (
            'block' =>
            array (
              'backgroundColor' => '#d52a2a',
              'borderColor' => '#0074a2',
              'borderWidth' => '0px',
              'borderRadius' => '0px',
              'borderStyle' => 'solid',
              'width' => '176px',
              'lineHeight' => '50px',
              'fontColor' => '#ffffff',
              'fontFamily' => 'Merriweather Sans',
              'fontSize' => '18px',
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
              'padding' => '18.5px',
              'borderStyle' => 'solid',
              'borderWidth' => '5px',
              'borderColor' => '#d52a2a',
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
          'contentType' => 'post',
          'postStatus' => 'publish',
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
          'iconSet' => 'full-symbol-color',
          'icons' =>
          array (
            0 =>
            array (
              'type' => 'socialIcon',
              'iconType' => 'instagram',
              'link' => 'http://instagram.com',
              'image' => $this->social_icon_url . '/06-full-symbol-color/Instagram.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Instagram',
            ),
            1 =>
            array (
              'type' => 'socialIcon',
              'iconType' => 'email',
              'link' => '',
              'image' => $this->social_icon_url . '/06-full-symbol-color/Email.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Email',
            ),
            2 =>
            array (
              'type' => 'socialIcon',
              'iconType' => 'website',
              'link' => '',
              'image' => $this->social_icon_url . '/06-full-symbol-color/Website.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Website',
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
              'height' => '118px',
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
              'fontFamily' => 'Merriweather Sans',
              'fontSize' => '12px',
              'textAlign' => 'center',
            ),
            'link' =>
            array (
              'fontColor' => '#d52a2a',
              'textDecoration' => 'underline',
            ),
          ),
          'type' => 'header',
        ),
      ),
    );
  }

}
