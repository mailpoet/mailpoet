/**
 * Color Picker behavior
 *
 * Adds a color picker integration with the view
 */
import Marionette from 'backbone.marionette';
import BehaviorsLookup from 'newsletter_editor/behaviors/BehaviorsLookup';
import MailPoet from 'mailpoet';
import 'spectrum'; // eslint-disable-line func-names

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
        chooseText: MailPoet.I18n.t('selectColor'),
        cancelText: MailPoet.I18n.t('cancelColorSelection'),
        change: updateColorInput,
        move: updateColorInput,
        hide: updateColorInput,
      });
    });
  },
});
