<?php

namespace MailPoet\Config\PopulatorData\Templates;
use MailPoet\WP\Functions as WPFunctions;

class BrandingAgencyNews {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/branding-agency-news';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => WPFunctions::get()->__("Branding Agency News", 'mailpoet'),
      'categories' => json_encode(array('standard', 'all')),
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
                'backgroundColor' => '#eeeeee',
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
                    'type' => 'header',
                    'text' => '<p><span style="color: #808080;"><a href="[link:newsletter_view_in_browser_url]" style="color: #808080;">Open this email in your web browser.</a></span></p>',
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
                        'textAlign' => 'left',
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
            ),
          ),
          2 => 
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
                        'backgroundColor' => 'transparent',
                        'height' => '25px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Branding-Logo.png',
                    'alt' => 'Branding-Logo',
                    'fullWidth' => false,
                    'width' => '122px',
                    'height' => '117px',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'textAlign' => 'left',
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
                    'type' => 'social',
                    'iconSet' => 'full-symbol-black',
                    'icons' => 
                    array (
                      0 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Facebook.png?mailpoet_version=3.15.0',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Twitter.png?mailpoet_version=3.15.0',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'instagram',
                        'link' => 'http://instagram.com',
                        'image' => $this->social_icon_url . '/07-full-symbol-black/Instagram.png?mailpoet_version=3.15.0',
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
                    'text' => '<p><span style="color: #bdbdbd;"><strong>B I G&nbsp; N E W S</strong></span></p>
    <h1>Branded is getting a refresh</h1>',
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
                  3 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Branding-Image01.jpg',
                    'alt' => 'Branding-Image01',
                    'fullWidth' => true,
                    'width' => '1200px',
                    'height' => '700px',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  4 => 
                  array (
                    'type' => 'spacer',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'height' => '30px',
                      ),
                    ),
                  ),
                  5 => 
                  array (
                    'type' => 'text',
                    'text' => '<p><span style="color: #999999;"><strong></strong></span><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam eu quam risus. Quisque tempor sodales tortor. Cras enim orci, bibendum vitae sollicitudin porttitor, eleifend eu metus. Aliquam a fringilla libero. Vivamus turpis orci, viverra in vehicula vitae, imperdiet et ex. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.</span></p>',
                  ),
                  6 => 
                  array (
                    'type' => 'button',
                    'text' => '> Read more',
                    'url' => '',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => '#ffffff',
                        'borderColor' => '#ffffff',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '90px',
                        'lineHeight' => '20px',
                        'fontColor' => '#0e0e0e',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '14px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'left',
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
                        'padding' => '13px',
                        'borderStyle' => 'solid',
                        'borderWidth' => '2px',
                        'borderColor' => '#dfdfdf',
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
                    'type' => 'text',
                    'text' => '<h3>Design starts with a pencil and paper</h3>
    <p><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam eu quam risus. Quisque tempor sodales tortor.</span></p>',
                  ),
                  1 => 
                  array (
                    'type' => 'button',
                    'text' => '> Read more',
                    'url' => '',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => '#ffffff',
                        'borderColor' => '#ffffff',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '90px',
                        'lineHeight' => '20px',
                        'fontColor' => '#0e0e0e',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '14px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'left',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Branding-Image02.jpg',
                    'alt' => 'Branding-Image02',
                    'fullWidth' => false,
                    'width' => '540px',
                    'height' => '700px',
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
                    'type' => 'text',
                    'text' => '<h3>How television has impacted branding</h3>
    <p><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam eu quam risus. Quisque tempor sodales tortor.</span></p>',
                  ),
                  1 => 
                  array (
                    'type' => 'button',
                    'text' => '> Read more',
                    'url' => '',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => '#ffffff',
                        'borderColor' => '#ffffff',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '90px',
                        'lineHeight' => '20px',
                        'fontColor' => '#0e0e0e',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '14px',
                        'fontWeight' => 'bold',
                        'textAlign' => 'left',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Branding-Image03.jpg',
                    'alt' => 'Branding-Image03',
                    'fullWidth' => false,
                    'width' => '1200px',
                    'height' => '700px',
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
          9 => 
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
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          10 => 
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
                'backgroundColor' => '#eeeeee',
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
                        'height' => '50px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          11 => 
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
                        'backgroundColor' => 'transparent',
                        'height' => '35px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'text',
                    'text' => '<h3 style="text-align: center;">Some of our recent branding</h3>
    <p style="text-align: center;">Looking for some work from us? Get in touch and we\'ll see what we can do.</p>',
                  ),
                  2 => 
                  array (
                    'type' => 'button',
                    'text' => 'Get in touch here >',
                    'url' => '',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => '#ffffff',
                        'borderColor' => '#ffffff',
                        'borderWidth' => '0px',
                        'borderRadius' => '0px',
                        'borderStyle' => 'solid',
                        'width' => '157px',
                        'lineHeight' => '20px',
                        'fontColor' => '#0e0e0e',
                        'fontFamily' => 'Merriweather Sans',
                        'fontSize' => '14px',
                        'fontWeight' => 'bold',
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
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          12 => 
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/greens-food-suppliers.png',
                    'alt' => 'greens-food-suppliers',
                    'fullWidth' => false,
                    'width' => '900px',
                    'height' => '418px',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/james-and-sons.png',
                    'alt' => 'james-and-sons',
                    'fullWidth' => false,
                    'width' => '156px',
                    'height' => '692px',
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
                  1 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/fast-banana.png',
                    'alt' => 'fast-banana',
                    'fullWidth' => false,
                    'width' => '900px',
                    'height' => '325px',
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
          13 => 
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/space-cube.png',
                    'alt' => 'space-cube',
                    'fullWidth' => false,
                    'width' => '900px',
                    'height' => '487px',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/the-dance-studio.png',
                    'alt' => 'the-dance-studio',
                    'fullWidth' => false,
                    'width' => '900px',
                    'height' => '365px',
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
                        'height' => '25px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/yoga-baby.png',
                    'alt' => 'yoga-baby',
                    'fullWidth' => false,
                    'width' => '900px',
                    'height' => '248px',
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
          14 => 
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
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          15 => 
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
                        'backgroundColor' => 'transparent',
                        'height' => '25px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/beauty-box.png',
                    'alt' => 'beauty-box',
                    'fullWidth' => false,
                    'width' => '900px',
                    'height' => '304px',
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/cheshire-county-hygiene-services.png',
                    'alt' => 'cheshire-county-hygiene-services',
                    'fullWidth' => false,
                    'width' => '900px',
                    'height' => '393px',
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
                        'height' => '25px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/crofts-accountants.png',
                    'alt' => 'crofts-accountants',
                    'fullWidth' => false,
                    'width' => '900px',
                    'height' => '229px',
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
          16 => 
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
                        'backgroundColor' => 'transparent',
                        'height' => '20px',
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
          17 => 
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
                'backgroundColor' => '#eeeeee',
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
                        'height' => '44px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/Branding-Logo.png',
                    'alt' => 'Branding-Logo',
                    'fullWidth' => false,
                    'width' => '120px',
                    'height' => '117px',
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
                    'type' => 'footer',
                    'text' => '<p><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br /><span style="color: #808080;">Add your postal address here!</span></p>',
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
                        'fontColor' => '#222222',
                        'textDecoration' => 'underline',
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
      'globalStyles' => 
      array (
        'text' => 
        array (
          'fontColor' => '#000000',
          'fontFamily' => 'Merriweather Sans',
          'fontSize' => '14px',
        ),
        'h1' => 
        array (
          'fontColor' => '#111111',
          'fontFamily' => 'Merriweather',
          'fontSize' => '36px',
        ),
        'h2' => 
        array (
          'fontColor' => '#222222',
          'fontFamily' => 'Merriweather',
          'fontSize' => '30px',
        ),
        'h3' => 
        array (
          'fontColor' => '#333333',
          'fontFamily' => 'Merriweather',
          'fontSize' => '24px',
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
          'backgroundColor' => '#eeeeee',
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
          'text' => 'Get in touch here >',
          'url' => '',
          'styles' => 
          array (
            'block' => 
            array (
              'backgroundColor' => '#ffffff',
              'borderColor' => '#ffffff',
              'borderWidth' => '0px',
              'borderRadius' => '0px',
              'borderStyle' => 'solid',
              'width' => '157px',
              'lineHeight' => '20px',
              'fontColor' => '#0e0e0e',
              'fontFamily' => 'Merriweather Sans',
              'fontSize' => '14px',
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
              'padding' => '13px',
              'borderStyle' => 'solid',
              'borderWidth' => '2px',
              'borderColor' => '#dfdfdf',
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
              'fontFamily' => 'Merriweather Sans',
              'fontSize' => '12px',
              'textAlign' => 'center',
            ),
            'link' => 
            array (
              'fontColor' => '#222222',
              'textDecoration' => 'underline',
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
              'iconType' => 'facebook',
              'link' => 'http://www.facebook.com',
              'image' => $this->social_icon_url . '/07-full-symbol-black/Facebook.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Facebook',
            ),
            1 => 
            array (
              'type' => 'socialIcon',
              'iconType' => 'twitter',
              'link' => 'http://www.twitter.com',
              'image' => $this->social_icon_url . '/07-full-symbol-black/Twitter.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Twitter',
            ),
            2 => 
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
              'fontFamily' => 'Merriweather Sans',
              'fontSize' => '12px',
              'textAlign' => 'left',
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
