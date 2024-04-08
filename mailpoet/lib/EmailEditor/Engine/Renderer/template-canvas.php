<?php declare(strict_types = 1);

// phpcs:disable Generic.Files.InlineHTML.Found
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
// phpcs:disable WordPressDotOrg.sniffs.OutputEscaping.UnescapedOutputParameter
/**
 * Template file to render the current 'wp_template', specifcally for emails.
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="format-detection" content="telephone=no" />
  <style type="text/css">
    <?php echo $templateStyles; ?>
  </style>
</head>
<body style="word-spacing:normal;background:<?php echo esc_attr($layoutBackground); ?>;">
    <div class="email_layout_wrapper" style="background:<?php echo esc_attr($layoutBackground); ?>">
      <!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" width="<?php echo esc_attr($width); ?>" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
      <div style="margin:0px auto;max-width:<?php echo esc_attr($width); ?>">
        <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
          <tbody>
            <tr>
              <td class="email_preheader" height="1">
                <?php echo $preHeader; ?>
              </td>
            </tr>
            <tr>
              <td class="email_content_wrapper" style="font-family: <?php echo esc_attr($contentFontFamily); ?>;padding: <?php echo esc_attr(implode(' ', [$padding['top'], '0px', $padding['bottom'], '0px'])); ?>;background: <?php echo esc_attr($contentBackground); ?>;">
                <?php echo $templateHtml; ?>
              </td>
            </tr>
            <tr>
              <td class="email_footer">
                <?php echo $logoHtml; ?>
              </td>
            </tr>
          </tbody>
        </table>
    </div>
    <!--[if mso | IE]></td></tr></table><![endif]-->
  </div>
</body>
</html>
