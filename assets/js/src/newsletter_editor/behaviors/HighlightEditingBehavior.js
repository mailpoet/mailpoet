/**
 * Highlight Editing Behavior
 *
 * Highlights a block,column that is being edited
 */
import Marionette from 'backbone.marionette';
import BL from 'newsletter_editor/behaviors/BehaviorsLookup';

BL.HighlightEditingBehavior = Marionette.Behavior.extend({
  modelEvents: {
    startEditing: 'enableHighlight',
    stopEditing: 'disableHighlight',
  },
  enableHighlight: function enableHighlight() {
    this.view._isBeingEdited = true;
    this.view.showTools();
    this.$el.addClass('mailpoet_highlight');
  },
  disableHighlight: function disableHighlight() {
    this.view._isBeingEdited = false;
    this.view.hideTools();
    this.$el.removeClass('mailpoet_highlight');
  },
});
