/**
 * ResizableBehavior
 *
 * Allows resizing elements within a block
 */
import Marionette from 'backbone.marionette';
import BehaviorsLookup from 'newsletter_editor/behaviors/BehaviorsLookup';
import interact from 'interact';
import _ from 'underscore';

var BL = BehaviorsLookup;

BL.ResizableBehavior = Marionette.Behavior.extend({
  defaults: {
    elementSelector: null,
    resizeHandleSelector: true, // true will use edges of the element itself
    // for blocks that use the default onResize function
    transformationFunction: function transformationFunction(y) { return y; },
    minLength: 0,
    maxLength: Infinity,
    modelField: 'styles.block.height',
    onResize: function onResize(event) {
      var currentLength = parseFloat(this.view.model.get(this.options.modelField));
      var newLength = currentLength + this.options.transformationFunction(event.dy);
      newLength = Math.min(this.options.maxLength, Math.max(this.options.minLength, newLength));
      this.view.model.set(this.options.modelField, newLength + 'px');
    },
  },
  events: {
    mouseenter: 'showResizeHandle',
    mouseleave: 'hideResizeHandle',
  },
  onRender: function onRender() {
    this.attachResize();

    if (this.isBeingResized !== true) {
      this.hideResizeHandle();
    }
  },
  attachResize: function attachResize() {
    var domElement;
    var that = this;
    if (this.options.elementSelector === null) {
      domElement = this.view.$el.get(0);
    } else {
      domElement = this.view.$(this.options.elementSelector).get(0);
    }
    interact(domElement).resizable({
      // axis: 'y',
      edges: {
        top: false,
        left: false,
        right: false,
        bottom: (typeof this.options.resizeHandleSelector === 'string') ? this.view.$(this.options.resizeHandleSelector).get(0) : this.options.resizeHandleSelector,
      },
    })
      .on('resizestart', function resizestart() {
        that.view.model.trigger('startEditing');
        that.isBeingResized = true;
        that.$el.addClass('mailpoet_resize_active');
      }).on('resizemove', function resizemove(event) {
        var onResize = that.options.onResize.bind(that);
        that.view.model.trigger('resizeMove', that.detectMousePointerFocus(event));
        return onResize(event);
      })
      .on('resizeend', function resizeend(event) {
        that.view.model.trigger('stopEditing');
        that.isBeingResized = null;
        if (!that.detectMousePointerFocus(event).isViewFocused) {
          that.hideResizeHandle();
        }
        that.$el.removeClass('mailpoet_resize_active');
      });
  },
  showResizeHandle: function showResizeHandle(mouseEvent) {
    // Skip if user is dragging/resizing
    if (!this.isBeingResized && mouseEvent && mouseEvent.buttons > 0) {
      return;
    }
    if (typeof this.options.resizeHandleSelector === 'string') {
      this.view.$(this.options.resizeHandleSelector).removeClass('mailpoet_hidden');
    }
  },
  hideResizeHandle: function hideResizeHandle() {
    if (typeof this.options.resizeHandleSelector === 'string') {
      this.view.$(this.options.resizeHandleSelector).addClass('mailpoet_hidden');
    }
  },
  detectMousePointerFocus: function detectMousePointerFocus(event) {
    var eventCopy = _.extend({}, event);
    var offset = this.view.$el.offset();
    var height = this.view.$el.height();
    var width = this.view.$el.width();
    if (event.pageX < offset.left
      || event.pageX > offset.left + width
      || event.pageY < offset.top
      || event.pageY > offset.top + height
    ) {
      eventCopy.isViewFocused = false;
    } else {
      eventCopy.isViewFocused = true;
    }
    return eventCopy;
  },
});
