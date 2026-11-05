/**
 * WooCommerce styles behavior
 *
 * Handles relations between different WC style settings (e.g. one color depends on another)
 */
import Marionette from 'backbone.marionette';
import { BehaviorsLookup } from 'newsletter-editor/behaviors/behaviors-lookup';

var BL = BehaviorsLookup;

BL.WooCommerceStylesBehavior = Marionette.Behavior.extend({
  // eslint-disable-next-line func-names
  events: function () {
    return {
      // eslint-disable-next-line func-names
      'change #mailpoet_wc_branding_color': function (event) {
        const brandingColor = event.target.value;
        this.view.model.set('woocommerce.brandingColor', brandingColor);
        this.view.model.set(
          'woocommerce.headingFontColor',
          this.getHeadingFontColor(brandingColor),
        );
        this.view.model.set(
          'link.fontColor',
          this.getLinkFontColor(
            brandingColor,
            this.view.model.get('wrapper.backgroundColor'),
          ),
        );
      },
      'change #mailpoet_newsletter_background_color':
        function changeContentBackgroundColor(event) {
          if (!this.view.isWoocommerceTransactional) {
            return;
          }
          this.view.model.set(
            'link.fontColor',
            this.getLinkFontColor(
              this.view.model.get('woocommerce.brandingColor'),
              event.target.value,
            ),
          );
        },
      'change #mailpoet_wc_heading_font_family':
        function changeHeadingfFontFamily(event) {
          this.view.model.set(
            'woocommerce.headingFontFamily',
            event.target.value,
          );
        },
      'change #mailpoet_wc_content_heading_font_color':
        function changeContentHeadingFontColor(event) {
          this.view.model.set(
            'woocommerce.contentHeadingFontColor',
            event.target.value,
          );
        },
    };
  },
  getHeadingFontColor(brandingColor) {
    return this.wcHexIsLight(brandingColor) ? '#202020' : '#ffffff';
  },
  // Keep in sync with TransactionalEmails::getWCEmailSettings(), which sets the link color on editor load.
  getLinkFontColor(brandingColor, backgroundColor) {
    const isBrandingLight = this.wcHexIsLight(brandingColor);
    const headingFontColor = this.getHeadingFontColor(brandingColor);
    if (this.wcHexIsLight(backgroundColor)) {
      return isBrandingLight ? headingFontColor : brandingColor;
    }
    return isBrandingLight ? brandingColor : headingFontColor;
  },
  // This is the wc_hex_is_light() WooCommerce function ported from PHP to JS.
  // Taken from https://stackoverflow.com/a/51567564
  // eslint-disable-next-line func-names
  wcHexIsLight: function (color) {
    const hex = color.replace('#', '');
    const colR = parseInt(hex.substr(0, 2), 16);
    const colG = parseInt(hex.substr(2, 2), 16);
    const colB = parseInt(hex.substr(4, 2), 16);
    const brightness = (colR * 299 + colG * 587 + colB * 114) / 1000;
    return brightness > 155;
  },
});
