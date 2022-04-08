/**
 * WooCommerce styles behavior
 *
 * Handles relations between different WC style settings (e.g. one color depends on another)
 */
import Marionette from 'backbone.marionette';
import BehaviorsLookup from 'newsletter_editor/behaviors/BehaviorsLookup';

var BL = BehaviorsLookup;

BL.WooCommerceStylesBehavior = Marionette.Behavior.extend({
  // eslint-disable-next-line func-names
  events: function () {
    return {
      // eslint-disable-next-line func-names
      'change #mailpoet_wc_branding_color': function (event) {
        let linkFontColor;
        const brandingColor = event.target.value;
        const headingFontColor = this.wcHexIsLight(brandingColor)
          ? '#202020'
          : '#ffffff';
        if (this.wcHexIsLight(this.view.model.get('wrapper.backgroundColor'))) {
          linkFontColor = this.wcHexIsLight(brandingColor)
            ? headingFontColor
            : brandingColor;
        } else {
          linkFontColor = this.wcHexIsLight(brandingColor)
            ? brandingColor
            : headingFontColor;
        }
        this.view.model.set('woocommerce.brandingColor', brandingColor);
        this.view.model.set('woocommerce.headingFontColor', headingFontColor);
        this.view.model.set('link.fontColor', linkFontColor);
      },
    };
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
