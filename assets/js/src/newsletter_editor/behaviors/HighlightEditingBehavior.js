/**
 * Highlight Editing Behavior
 *
 * Highlights a block,column that is being hovered by mouse or edited
 */
import Marionette from 'backbone.marionette';
import BL from 'newsletter_editor/behaviors/BehaviorsLookup';

BL.HighlightEditingBehavior = Marionette.Behavior.extend({
  modelEvents: {
    startEditing: 'onStartEditing',
    stopEditing: 'onStopEditing',
    startResizing: 'onStartResizing',
    stopResizing: 'onStopResizing',
    resizeMove: 'onResizeMove',
  },
  events: {
    mouseenter: 'onMouseEnter',
    mouseleave: 'onMouseLeave',
  },
  // mouseleave event is not always triggered during resizing
  // so we have to check if the pointer is still inside using resize event coordinates
  onResizeMove: function onResizeMove(event) {
    this.isFocusedByPointer = event.isViewFocused;
  },
  onMouseEnter: function onMouseEnter(mouseEvent) {
    this.isFocusedByPointer = true;
    // Ignore mouse events when dragging
    if (mouseEvent && mouseEvent.buttons > 0) {
      return;
    }
    this.view.addHighlight();
  },
  onMouseLeave: function onMouseLeave() {
    this.isFocusedByPointer = false;
    // Ignore mouse events when item is being edited
    if (this.isBeingEdited) {
      return;
    }
    this.view.removeHighlight();
  },
  onStartEditing: function onStartEditing() {
    this.isBeingEdited = true;
    this.view.addHighlight();
  },
  onStopEditing: function onStopEditing() {
    this.isBeingEdited = false;
    if (!this.isFocusedByPointer) {
      this.view.removeHighlight();
    }
  },
  onStartResizing: function onStartResizing() {
    this.onStartEditing();
    this.view.triggerMethod('resizeStart');
  },
  onStopResizing: function onStopResizing(event) {
    this.onStopEditing();
    this.view.triggerMethod('resizeStop', event);
  },
  onDomRefresh: function onDomRefresh() {
    if (this.isBeingEdited) {
      this.view.addHighlight();
    }
  },
  onChildviewResizeStart: function onChildviewResizeStart() {
    // Let event bubble up
    this.view.triggerMethod('resizeStart');
  },
  onChildviewResizeStop: function onChildviewResizeStop(event) {
    // Let event bubble up
    this.view.triggerMethod('resizeStop', event);
  },
});
