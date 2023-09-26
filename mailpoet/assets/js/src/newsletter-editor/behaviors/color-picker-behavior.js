/**
 * Color Picker behavior
 *
 * Adds a color picker integration with the view
 */
import Marionette from 'backbone.marionette';
import 'spectrum'; // eslint-disable-line func-names
import { BehaviorsLookup } from 'newsletter-editor/behaviors/behaviors-lookup';
import { _x } from '@wordpress/i18n';

var BL = BehaviorsLookup;

BL.ColorPickerBehavior = Marionette.Behavior.extend({
  // eslint-disable-next-line func-names
  onRender: function () {
    var that = this;
    var preferredFormat = 'hex6';
    // eslint-disable-next-line func-names
    this.view.$('.mailpoet_color').each(function () {
      var $input = that.view.$(this);
      // eslint-disable-next-line func-names
      var updateColorInput = function (color) {
        if (color && color.getAlpha() > 0) {
          $input.val(color.toString(preferredFormat));
        } else {
          $input.val('');
        }
        $input.trigger('change');
      };
      $input.spectrum({
        clickoutFiresChange: true,
        showInput: true,
        showInitial: true,
        showPalette: true,
        showSelectionPalette: true,
        palette: [],
        localStorageKey: 'newsletter_editor.spectrum.palette',
        preferredFormat: preferredFormat,
        allowEmpty: true,
        chooseText: _x('Select', 'select color', 'mailpoet'),
        cancelText: _x('Cancel', 'cancel color selection', 'mailpoet'),
        change: updateColorInput,
        move: updateColorInput,
        hide: updateColorInput,
      });
    });
  },
});
