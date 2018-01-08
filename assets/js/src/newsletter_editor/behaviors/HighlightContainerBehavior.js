/**
 * Highlight Container Behavior
 *
 * Highlights a container block when hovering over its tools
 */
define([
  'backbone.marionette',
  'newsletter_editor/behaviors/BehaviorsLookup'
], function (Marionette, BehaviorsLookup) { // eslint-disable-line func-names
  var BL = BehaviorsLookup;

  BL.HighlightContainerBehavior = Marionette.Behavior.extend({
    events: {
      'mouseenter @ui.tools': 'enableHighlight',
      'mouseleave @ui.tools': 'disableHighlight'
    },
    enableHighlight: function () { // eslint-disable-line func-names
      this.$el.addClass('mailpoet_highlight');
    },
    disableHighlight: function () { // eslint-disable-line func-names
      if (!this.view._isBeingEdited) {
        this.$el.removeClass('mailpoet_highlight');
      }
    }
  });
});
