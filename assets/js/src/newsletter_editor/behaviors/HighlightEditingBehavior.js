/**
 * Highlight Editing Behavior
 *
 * Highlights a block that is being edited
 */
define([
  'backbone.marionette',
  'newsletter_editor/behaviors/BehaviorsLookup'
], function(Marionette, BehaviorsLookup) {
  var BL = BehaviorsLookup;

  BL.HighlightEditingBehavior = Marionette.Behavior.extend({
    modelEvents: {
      startEditing: 'enableHighlight',
      stopEditing: 'disableHighlight'
    },
    enableHighlight: function() {
      this.view._isBeingEdited = true;
      this.$el.addClass('mailpoet_highlight');
    },
    disableHighlight: function() {
      this.view._isBeingEdited = false;
      this.$el.removeClass('mailpoet_highlight');
    }
  });
});
