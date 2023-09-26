/**
 * Highlight Editing Behavior
 *
 * Highlights a block,column that is being hovered by mouse or edited
 */
import Marionette from 'backbone.marionette';
import { BehaviorsLookup } from 'newsletter-editor/behaviors/behaviors-lookup';
import { App } from 'newsletter-editor/app';
import { isEventInsideElement } from 'newsletter-editor/utils';

BehaviorsLookup.HighlightEditingBehavior = Marionette.Behavior.extend({
  modelEvents: {
    startEditing: 'onStartEditing',
    stopEditing: 'onStopEditing',
    startResizing: 'onStartResizing',
    stopResizing: 'onStopResizing',
  },
  events: {
    mouseenter: 'onMouseEnter',
    mouseleave: 'onMouseLeave',
  },
  onMouseEnter: function onMouseEnter(mouseEvent) {
    this.isFocusedByPointer = true;
    // Ignore mouse events when dragging
    if (mouseEvent && mouseEvent.buttons > 0) {
      return;
    }
    // Ignore mouse events when settings panel is showed
    if (App.getDisplayedSettingsId()) {
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
    this.isFocusedByPointer = isEventInsideElement(event, this.view.$el);
    if (!App.getDisplayedSettingsId()) {
      this.onStopEditing();
    }
    this.view.triggerMethod('resizeStop', event);
  },
  onDomRefresh: function onDomRefresh() {
    if (this.isBeingEdited) {
      this.view.addHighlight();
    }
  },
  onChildviewResizeStart: function onChildviewResizeStart() {
    this.onStartEditing();
    // Let event bubble up
    this.view.triggerMethod('resizeStart');
  },
  onChildviewResizeStop: function onChildviewResizeStop(event) {
    this.isFocusedByPointer = isEventInsideElement(event, this.view.$el);
    this.onStopEditing();
    // Let event bubble up
    this.view.triggerMethod('resizeStop', event);
  },
});
