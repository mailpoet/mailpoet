/**
 * Highlight Editing Behavior
 *
 * Highlights a block that is being edited
 */
define([
    'backbone.marionette',
    'newsletter_editor/behaviors/BehaviorsLookup',
  ], function(Marionette, BehaviorsLookup) {

  BehaviorsLookup.HighlightEditingBehavior = Marionette.Behavior.extend({
    modelEvents: {
      'startEditing': 'enableHighlight',
      'stopEditing': 'disableHighlight',
    },
    enableHighlight: function() {
      this.$el.addClass('mailpoet_highlight');
    },
    disableHighlight: function() {
      this.$el.removeClass('mailpoet_highlight');
    },
  });
});
