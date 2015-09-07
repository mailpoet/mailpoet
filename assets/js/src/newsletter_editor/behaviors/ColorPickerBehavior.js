/**
 * Color Picker behavior
 *
 * Adds a color picker integration with the view
 */
define([
    'backbone.marionette',
    'newsletter_editor/behaviors/BehaviorsLookup',
    'spectrum',
  ], function(Marionette, BehaviorsLookup) {

  BehaviorsLookup.ColorPickerBehavior = Marionette.Behavior.extend({
    onRender: function() {
      this.view.$('.mailpoet_color').spectrum({
        clickoutFiresChange: true,
        showInput: true,
        showInitial: true,
        preferredFormat: "hex6",
        allowEmpty: true,
      });
    },
  });
});
