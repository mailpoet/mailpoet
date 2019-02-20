<?php

namespace MailPoet\Config\PopulatorData\Templates;
use MailPoet\WP\Functions as WPFunctions;

class College {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/college';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => WPFunctions::get()->__("College", 'mailpoet'),
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
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/College-Logo.png',
                    'alt' => 'College-Logo',
                    'fullWidth' => false,
                    'width' => '106px',
                    'height' => '652px',
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
                        'height' => '36px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'social',
                    'iconSet' => 'circles',
                    'icons' => 
                    array (
                      0 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/03-circles/Facebook.png?mailpoet_version=3.15.0',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/03-circles/Twitter.png?mailpoet_version=3.15.0',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'instagram',
                        'link' => 'http://instagram.com',
                        'image' => $this->social_icon_url . '/03-circles/Instagram.png?mailpoet_version=3.15.0',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Instagram',
                      ),
                      3 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'youtube',
                        'link' => 'http://www.youtube.com',
                        'image' => $this->social_icon_url . '/03-circles/Youtube.png?mailpoet_version=3.15.0',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Youtube',
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
                        'height' => '33px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'header',
                    'text' => '<p><a href="[link:newsletter_view_in_browser_url]">View in browser &gt;</a></p>',
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
                        'textAlign' => 'right',
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
          2 => 
          array (
            'type' => 'container',
            'columnLayout' => false,
            'orientation' => 'horizontal',
            'image' => 
            array (
              'src' => $this->template_image_url . '/College-Header.jpg',
              'display' => 'scale',
            ),
            'styles' => 
            array (
              'block' => 
              array (
                'backgroundColor' => '#333333',
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
                    'type' => 'text',
                    'text' => '<p style="text-align: left;"><span style="color: #ffcc00;"><strong>S U M M E R&nbsp; &nbsp;E D I T I O N</strong></span></p>
    <h1 style="text-align: left;"><span style="color: #ffffff;">Graduation Day is Coming</span></h1>
    <p style="text-align: left;"><span style="color: #ffffff;"><span>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce dapibus iaculis risus ut tincidunt. Integer gravida mauris erat, vehicula blandit velit ultrices ut.&nbsp;</span></span></p>',
                  ),
                  2 => 
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
                ),
              ),
            ),
          ),
          3 => 
          array (
            'type' => 'container',
            'columnLayout' => '2_1',
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
                        'height' => '54px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: left;"><span style="color: #999999;"><strong>H E A D M A S T E R \' S&nbsp; &nbsp;B L O G</strong></span></p>
    <h2 style="text-align: left;"><strong><span style="color: #333333;">Your hard work pays off in the future to come</span></strong></h2>
    <p></p>',
                  ),
                  2 => 
                  array (
                    'type' => 'divider',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'backgroundColor' => 'transparent',
                        'padding' => '18.5px',
                        'borderStyle' => 'solid',
                        'borderWidth' => '2px',
                        'borderColor' => '#ffcc00',
                      ),
                    ),
                  ),
                  3 => 
                  array (
                    'type' => 'text',
                    'text' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur purus quam, semper eget nisl at, pretium fermentum ligula. Ut augue elit, congue non nisl eget, venenatis ornare ante. Aliquam erat volutpat. Maecenas facilisis eget risus vel scelerisque.</p>
    <p></p>
    <p>Etiam eros arcu, vestibulum at iaculis ut, iaculis nec est. Ut ut malesuada purus. Etiam sed accumsan eros, eu aliquam nisl. Nam faucibus sit amet felis at euismod. Sed efficitur varius tincidunt. Curabitur varius, nunc id interdum sodales, felis purus congue nulla, at semper orci est id mauris. Ut euismod vel dui sed mollis. Aliquam vestibulum id leo eu tristique. Etiam sit amet lacus vitae mi dignissim iaculis id ac nulla.</p>
    <p></p>',
                  ),
                  4 => 
                  array (
                    'type' => 'image',
                    'link' => '',
                    'src' => $this->template_image_url . '/College-Image.jpg',
                    'alt' => 'College-Image',
                    'fullWidth' => false,
                    'width' => '1200px',
                    'height' => '800px',
                    'styles' => 
                    array (
                      'block' => 
                      array (
                        'textAlign' => 'center',
                      ),
                    ),
                  ),
                  5 => 
                  array (
                    'type' => 'text',
                    'text' => '<p></p>
    <p>Praesent mi lorem, varius sit amet mattis at, sollicitudin ac enim. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam rhoncus tellus eleifend, accumsan magna in, imperdiet mauris. Cras a justo in lectus semper fermentum. Integer porta vel risus a varius. Aliquam nec nulla dui. Nulla congue ornare fringilla.</p>
    <p></p>
    <p>Nulla gravida interdum velit, nec pretium lacus placerat vel. Curabitur vitae arcu condimentum, iaculis nisl ac, viverra augue.</p>
    <p></p>
    <p>Etiam tincidunt justo quam, ac suscipit lectus hendrerit ac.</p>',
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
                    'src' => $this->template_image_url . '/College-Avatar.jpg',
                    'alt' => 'College-Avatar',
                    'fullWidth' => true,
                    'width' => '500px',
                    'height' => '500px',
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
                    'type' => 'text',
                    'text' => '<p></p>
    <p></p>
    <p><strong>Mr George Chalk</strong></p>
    <p>Headmaster &amp; Director</p>',
                  ),
                  2 => 
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
                  3 => 
                  array (
                    'type' => 'text',
                    'text' => '<p><strong>Need some tips and advice?&nbsp;</strong></p>
    <p>Here\'s the latest updates from our blog:</p>
    <p></p>
    <p><a href="http://mailpoet-sg.com">7 ways to destress before an exam even starts</a></p>
    <p></p>
    <p><a href="http://mailpoet-sg.com">Focussing on the right content is key to being successful</a></p>
    <p></p>
    <p><a href="http://mailpoet-sg.com">Getting a degree is only the start of the battle</a></p>',
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
          5 => 
          array (
            'type' => 'container',
            'columnLayout' => '2_1',
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
                        'height' => '35px',
                      ),
                    ),
                  ),
                  1 => 
                  array (
                    'type' => 'social',
                    'iconSet' => 'circles',
                    'icons' => 
                    array (
                      0 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'facebook',
                        'link' => 'http://www.facebook.com',
                        'image' => $this->social_icon_url . '/03-circles/Facebook.png?mailpoet_version=3.15.0',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Facebook',
                      ),
                      1 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'twitter',
                        'link' => 'http://www.twitter.com',
                        'image' => $this->social_icon_url . '/03-circles/Twitter.png?mailpoet_version=3.15.0',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Twitter',
                      ),
                      2 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'instagram',
                        'link' => 'http://instagram.com',
                        'image' => $this->social_icon_url . '/03-circles/Instagram.png?mailpoet_version=3.15.0',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Instagram',
                      ),
                      3 => 
                      array (
                        'type' => 'socialIcon',
                        'iconType' => 'youtube',
                        'link' => 'http://www.youtube.com',
                        'image' => $this->social_icon_url . '/03-circles/Youtube.png?mailpoet_version=3.15.0',
                        'height' => '32px',
                        'width' => '32px',
                        'text' => 'Youtube',
                      ),
                    ),
                  ),
                  2 => 
                  array (
                    'type' => 'text',
                    'text' => '<p style="text-align: center;"><span style="color: #ffcc00;"><strong></strong></span><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a><span>&nbsp;|&nbsp;</span><a href="[link:subscription_manage_url]">Manage subscription</a><br /><span>Add your postal address here!</span></p>',
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
                    'src' => $this->template_image_url . '/College-Logo.png',
                    'alt' => 'College-Logo',
                    'fullWidth' => false,
                    'width' => '106px',
                    'height' => '652px',
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
        ),
      ),
      'globalStyles' => 
      array (
        'text' => 
        array (
          'fontColor' => '#000000',
          'fontFamily' => 'Source Sans Pro',
          'fontSize' => '14px',
        ),
        'h1' => 
        array (
          'fontColor' => '#111111',
          'fontFamily' => 'Playfair Display',
          'fontSize' => '40px',
        ),
        'h2' => 
        array (
          'fontColor' => '#222222',
          'fontFamily' => 'Playfair Display',
          'fontSize' => '30px',
        ),
        'h3' => 
        array (
          'fontColor' => '#333333',
          'fontFamily' => 'Open Sans',
          'fontSize' => '24px',
        ),
        'link' => 
        array (
          'fontColor' => '#447c9d',
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
          'text' => 'Button',
          'url' => '',
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
        'divider' => 
        array (
          'styles' => 
          array (
            'block' => 
            array (
              'backgroundColor' => 'transparent',
              'padding' => '18.5px',
              'borderStyle' => 'solid',
              'borderWidth' => '2px',
              'borderColor' => '#ffcc00',
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
          'iconSet' => 'circles',
          'icons' => 
          array (
            0 => 
            array (
              'type' => 'socialIcon',
              'iconType' => 'facebook',
              'link' => 'http://www.facebook.com',
              'image' => $this->social_icon_url . '/03-circles/Facebook.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Facebook',
            ),
            1 => 
            array (
              'type' => 'socialIcon',
              'iconType' => 'twitter',
              'link' => 'http://www.twitter.com',
              'image' => $this->social_icon_url . '/03-circles/Twitter.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Twitter',
            ),
            2 => 
            array (
              'type' => 'socialIcon',
              'iconType' => 'instagram',
              'link' => 'http://instagram.com',
              'image' => $this->social_icon_url . '/03-circles/Instagram.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Instagram',
            ),
            3 => 
            array (
              'type' => 'socialIcon',
              'iconType' => 'youtube',
              'link' => 'http://www.youtube.com',
              'image' => $this->social_icon_url . '/03-circles/Youtube.png',
              'height' => '32px',
              'width' => '32px',
              'text' => 'Youtube',
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
              'textAlign' => 'right',
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
