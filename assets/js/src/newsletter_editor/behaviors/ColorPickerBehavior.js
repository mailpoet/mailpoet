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
  ], function(Marionette, BehaviorsLookup, MailPoet, Spectrum) {
  var BL = BehaviorsLookup;

  BL.ColorPickerBehavior = Marionette.Behavior.extend({
    onRender: function() {
      var that = this, 
        preferredFormat = 'hex6';
      this.view.$('.mailpoet_color').each(function () {
        var $input = that.view.$(this);
        var updateColorInput = function(color) {
          $input.val(color.toString(preferredFormat));
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
          move: updateColorInput,
          hide: updateColorInput
        });
      });        
    }
  });
});
