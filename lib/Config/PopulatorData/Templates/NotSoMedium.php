<?php

namespace MailPoet\Config\PopulatorData\Templates;

class NotSoMedium {

  private $template_image_url;
  private $social_icon_url;

  function __construct($assets_url) {
    $this->template_image_url = 'https://ps.w.org/mailpoet/assets/newsletter-templates/not-so-medium';
    $this->social_icon_url = $assets_url . '/img/newsletter_editor/social-icons';
  }

  function get() {
    return array(
      'name' => __("One Full Post In An Email", 'mailpoet'),
      'categories' => json_encode(array('notification', 'sample')),
      'readonly' => 1,
      'thumbnail' => $this->getThumbnail(),
      'body' => json_encode($this->getBody()),
    );
  }

  private function getThumbnail() {
    return $this->template_image_url . '/not-so-medium.jpg';
  }

  private function getBody() {
    return array (
      'content' =>
        array (
          'type' => 'container',
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
                                          'height' => '30px',
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
                                  'src' => $this->template_image_url . '/Not-So-Medium-Logo.png',
                                  'alt' => 'Not-So-Medium-Logo',
                                  'fullWidth' => false,
                                  'width' => '210px',
                                  'height' => '90px',
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
                                          'height' => '40px',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                      2 =>
                        array (
                          'type' => 'container',
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
                                          'image' => $this->social_icon_url . '/07-full-symbol-black/Facebook.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Facebook',
                                        ),
                                      1 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'twitter',
                                          'link' => 'http://www.twitter.com',
                                          'image' => $this->social_icon_url . '/07-full-symbol-black/Twitter.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Twitter',
                                        ),
                                      2 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'website',
                                          'link' => '',
                                          'image' => $this->social_icon_url . '/07-full-symbol-black/Website.png?mailpoet_version=3.0.0-rc.2.0.0',
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
                          'backgroundColor' => '#ebebeb',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
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
                                  'text' => '<p><strong><em>Welcome to this week\'s post. </em></strong></p>
<p><em>Every Friday, we send you the most interesting story&nbsp;of the week in full.</em></p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'spacer',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'backgroundColor' => '#d6d6d6',
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
                                  'text' => '<h1 style="text-align: left; line-height: 34px;" data-post-id="2130"><span style="color: #333333;"><strong>Science busts the biggest myth ever about why bridges collapse</strong></span></h1>
<blockquote class="graf graf--blockquote graf--startsWithDoubleQuote graf--leading">
<p class="mailpoet_wp_post"><span style="color: #808080;"><em class="markup--em markup--blockquote-em">&ldquo;At least six lamp posts were snapped off while I watched. A few minutes later, I saw a side girder bulge out. But, though the bridge was bucking up at an angle of 45 degrees, I thought she would be able to fight it out. But, that wasn&rsquo;t to be.&rdquo; -</em>Bert Farquharson</span></p>
</blockquote>
<p class="graf graf--p graf-after--blockquote"><span style="color: #333333;">The collapse of the Tacoma Narrows Bridge on the morning of November 7, 1940, is the most iconic example of a spectacular bridge failure in modern times. As the third largest suspension bridge in the world, behind only the George Washington and Golden Gate bridges, it connected Tacoma to the entire Kitsap Peninsula in Puget Sound, and opened to the public on July 1st, 1940. Just four months later, under the right wind conditions, the bridge was driven at its resonant frequency, causing it to oscillate and twist uncontrollably. After undulating for over an hour, the middle section collapsed, and the bridge was destroyed. It was a testimony to the power of resonance, and has been used as a classic example in physics and engineering classes across the country ever since. Unfortunately, the story is a complete myth.</span></p>
<p class="mailpoet_wp_post"><span style="color: #333333;">Every physical system or object has a frequency that&rsquo;s naturally inherent to it: its resonant frequency. A swing, for example, has a certain frequency you can drive it at; as a child you learn to pump yourself in time with the swing. Pump too slowly or too quickly, and you&rsquo;ll never build up speed, but if you pump at just the right rate, you can swing as high as your muscles will take you. Resonant frequencies can also be disastrous if you build up too much vibrational energy in a system that can&rsquo;t handle it, which is how sound alone at just the right pitch is capable of causing a wine glass to shatter.</span></p>',
                                ),
                              2 =>
                                array (
                                  'type' => 'image',
                                  'link' => $this->template_image_url . '/0-w9nQeKIWx4fZbwPD.gif',
                                  'src' => $this->template_image_url . '/0-w9nQeKIWx4fZbwPD.gif',
                                  'alt' => '',
                                  'fullWidth' => true,
                                  'width' => '400',
                                  'height' => '225',
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
                                  'type' => 'text',
                                  'text' => '<p><span style="color: #808080; font-size: 12px; line-height: 12px;">A wine glass, stimulated by a continued sound at just the right pitch/frequency, will vibrate at such a frequency that the internal stresses will destroy it. Image credit: Marty33 of&nbsp;YouTube.</span></p>',
                                ),
                              4 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p class="mailpoet_wp_post"><span style="color: #333333;">It makes sense, looking at what happened to the bridge, that resonance would be the culprit.&nbsp;<span class="markup--quote markup--p-quote is-other" data-creator-ids="anon">And that&rsquo;s the easiest pitfall in science: when you come up with an explanation that&rsquo;s simple, compelling, and appears obvious. Because in this case, it&rsquo;s completely wrong.</span>&nbsp;You can calculate what the resonant frequency of the bridge would be, and there was nothing driving at that frequency. All you had was a sustained, strong wind. In fact, the bridge itself wasn&rsquo;t undulating at its resonant frequency at all!</span></p>
<p class="graf graf--p graf-after--p"><span style="color: #333333;">But the story of what was actually happening was fascinating, and holds lessons &mdash; lessons we haven&rsquo;t necessarily heeded &mdash; for all the bridges we&rsquo;ve built ever since.</span></p>',
                                ),
                              5 =>
                                array (
                                  'type' => 'image',
                                  'link' => $this->template_image_url . '/0-PpT1Hf-4dqFdvSxR.jpeg',
                                  'src' => $this->template_image_url . '/0-PpT1Hf-4dqFdvSxR.jpeg',
                                  'alt' => '',
                                  'fullWidth' => true,
                                  'width' => '800',
                                  'height' => '488',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              6 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p><span style="color: #808080; font-size: 12px; line-height: 12px;"><span>Capilano bridge in Vancouver, Canada, is one of the world&rsquo;s largest pedestrian suspension bridges. If you walk across it, you&rsquo;ll leave disoriented from the undulations. Image credit: Leonard G. of English Wikipedia.</span></span></p>',
                                ),
                              7 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p class="mailpoet_wp_post"><span style="color: #333333;">Whenever you have an object suspended between two points, it&rsquo;s free to move, vibrate, oscillate, etc. It has its own response to outside stimuli, just like a guitar string vibrates in response to outside excitations. That&rsquo;s what the bridge did most of the time: simply vibrated up-and-down as cars passed over it, as the wind blew, etc. It did what any suspension bridge would do, only slightly more severely due to the cost-saving measures implemented in its construction. Structures like bridges are particularly good at shedding this kind of energy, so that, on its own, posed no danger of collapse.</span></p>
<p class="graf graf--p graf-after--figure"><span style="color: #333333;">But as the wind passed over the bridge on November 7th, a stronger, more sustained wind than it had ever experienced before, causing vortices to form as the steady wind passed over the bridge. In small doses, this wouldn&rsquo;t pose much of a problem, but take a look at the effects of these vortices on a structure in the video below.</span></p>
<p class="mailpoet_wp_post"><span style="color: #333333;">Over time, they cause a aerodynamic phenomenon known as &ldquo;flutter,&rdquo; where the extremities in the direction of the wind get an extra rocking motion to them. This causes the outer portions to move perpendicular to the wind direction, but out-of-phase from the overall up-and-down motion of the bridge. This phenomenon of flutter has been&nbsp;<a href="http://www.iitk.ac.in/infocell/Archive/dirjan1/flutter.html" class="markup--anchor markup--p-anchor" target="_blank" style="color: #333333;" data-href="http://www.iitk.ac.in/infocell/Archive/dirjan1/flutter.html" rel="noopener noreferrer">known to be disastrous for aircraft</a>, but it was never seen in a bridge before. At least, not to this extent.</span></p>',
                                ),
                              8 =>
                                array (
                                  'type' => 'image',
                                  'link' => $this->template_image_url . '/0-IM2va2_e6Mzg1Et-.jpeg',
                                  'src' => $this->template_image_url . '/0-IM2va2_e6Mzg1Et-.jpeg',
                                  'alt' => '',
                                  'fullWidth' => true,
                                  'width' => '960',
                                  'height' => '517',
                                  'styles' =>
                                    array (
                                      'block' =>
                                        array (
                                          'textAlign' => 'center',
                                        ),
                                    ),
                                ),
                              9 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p><span style="color: #808080; font-size: 12px; line-height: 12px;"><span>Under the effects of flutter, aircraft wings can bend or even break off entirely. This has let to the demise of a number of pilots and numerous plane crashes over the years. Image credit: Netherlands Aerospace Center /&nbsp;NLR.</span></span></p>',
                                ),
                              10 =>
                                array (
                                  'type' => 'text',
                                  'text' => '<p class="mailpoet_wp_post"><span style="color: #333333;">When the flutter effect began, one of the steel suspension cables supporting the bridge snapped, removing the last major obstacle to this fluttering motion. That was when the additional undulations, where the two sides of the bridge rocked back-and-forth in harmony with one another, began in earnest. With the sustained, strong winds, the continued vortices, and no ability to dissipate those forces, the bridge&rsquo;s rocking continued unabated, and even intensified. The last humans on the bridge, the photographers, fled the scene.</span></p>
<p class="mailpoet_wp_post"><span style="color: #333333;">Don&rsquo;t blame resonance for the most famous bridge-collapse of all. The true cause is much scarier, and could affect hundreds of bridges across the world if we ever forget to account for, and mitigate, the fluttering effects that brought this one down.</span></p>',
                                ),
                              11 =>
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
              4 =>
                array (
                  'type' => 'container',
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
                          'backgroundColor' => '#ebebeb',
                        ),
                    ),
                  'blocks' =>
                    array (
                      0 =>
                        array (
                          'type' => 'container',
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
                                  'text' => '<p style="text-align: center;"><strong><em>Share this with your friends</em></strong></p>
<p style="text-align: center;"><em>We promise not to spam anyone, and we only send our great articles to any email addresses in our list. Promise!</em></p>',
                                ),
                              2 =>
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
                                          'image' => $this->social_icon_url . '/03-circles/Facebook.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Facebook',
                                        ),
                                      1 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'twitter',
                                          'link' => 'http://www.twitter.com',
                                          'image' => $this->social_icon_url . '/03-circles/Twitter.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Twitter',
                                        ),
                                      2 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'google-plus',
                                          'link' => 'http://plus.google.com',
                                          'image' => $this->social_icon_url . '/03-circles/Google-Plus.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Google Plus',
                                        ),
                                      3 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'linkedin',
                                          'link' => 'http://www.linkedin.com',
                                          'image' => $this->social_icon_url . '/03-circles/LinkedIn.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'LinkedIn',
                                        ),
                                      4 =>
                                        array (
                                          'type' => 'socialIcon',
                                          'iconType' => 'email',
                                          'link' => '',
                                          'image' => $this->social_icon_url . '/03-circles/Email.png?mailpoet_version=3.0.0-rc.2.0.0',
                                          'height' => '32px',
                                          'width' => '32px',
                                          'text' => 'Email',
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
                                          'backgroundColor' => '#d6d6d6',
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
                                  'type' => 'footer',
                                  'text' => '<p><strong><a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage your subscription</a></strong><br />Add your postal address here!</p>',
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
                                          'fontColor' => '#2c2c2c',
                                          'textDecoration' => 'none',
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
              'fontFamily' => 'Georgia',
              'fontSize' => '18px',
            ),
          'h1' =>
            array (
              'fontColor' => '#111111',
              'fontFamily' => 'Arial',
              'fontSize' => '30px',
            ),
          'h2' =>
            array (
              'fontColor' => '#222222',
              'fontFamily' => 'Arial',
              'fontSize' => '24px',
            ),
          'h3' =>
            array (
              'fontColor' => '#333333',
              'fontFamily' => 'Arial',
              'fontSize' => '22px',
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
              'backgroundColor' => '#ffffff',
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
                      'padding' => '13px',
                      'borderStyle' => 'solid',
                      'borderWidth' => '3px',
                      'borderColor' => '#aaaaaa',
                    ),
                ),
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
              'iconSet' => 'default',
              'icons' =>
                array (
                  0 =>
                    array (
                      'type' => 'socialIcon',
                      'iconType' => 'facebook',
                      'link' => 'http://www.facebook.com',
                      'image' => $this->social_icon_url . '/01-social/Facebook.png?mailpoet_version=3.9.1',
                      'height' => '32px',
                      'width' => '32px',
                      'text' => 'Facebook',
                    ),
                  1 =>
                    array (
                      'type' => 'socialIcon',
                      'iconType' => 'twitter',
                      'link' => 'http://www.twitter.com',
                      'image' => $this->social_icon_url . '/01-social/Twitter.png?mailpoet_version=3.9.1',
                      'height' => '32px',
                      'width' => '32px',
                      'text' => 'Twitter',
                    ),
                ),
            ),
          'spacer' =>
            array (
              'styles' =>
                array (
                  'block' =>
                    array (
                      'backgroundColor' => 'transparent',
                      'height' => '40px',
                    ),
                ),
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
            ),
        ),
    );
  }

}
