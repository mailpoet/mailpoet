/**
 * Color Picker behavior
 *
 * Adds a color picker integration with the view
 */
define([
  'backbone.marionette',
  'newsletter_editor/behaviors/BehaviorsLookup',
  'mailpoet',
  'spectrum'
], function (Marionette, BehaviorsLookup, MailPoet) { // eslint-disable-line func-names
  var BL = BehaviorsLookup;

  BL.ColorPickerBehavior = Marionette.Behavior.extend({
    onRender: function () { // eslint-disable-line func-names
      var that = this;
      var preferredFormat = 'hex6';
      this.view.$('.mailpoet_color').each(function () { // eslint-disable-line func-names
        var $input = that.view.$(this);
        var updateColorInput = function (color) { // eslint-disable-line func-names
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
          hide: updateColorInput
        });
      });
    }
  });
});
