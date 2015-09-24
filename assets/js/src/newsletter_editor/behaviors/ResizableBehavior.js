/**
 * ResizableBehavior
 *
 * Allows resizing elements within a block
 */
define([
    'backbone.marionette',
    'newsletter_editor/behaviors/BehaviorsLookup',
    'interact'
  ], function(Marionette, BehaviorsLookup, interact) {

  BehaviorsLookup.ResizableBehavior = Marionette.Behavior.extend({
    defaults: {
      elementSelector: null,
      resizeHandleSelector: true, // true will use edges of the element itself
      transformationFunction: function(y) { return y; },
      minLength: 0,
      modelField: 'styles.block.height',
    },
    events: {
      "mouseenter": 'showResizeHandle',
      "mouseleave": 'hideResizeHandle',
    },
    onRender: function() {
      this.attachResize();

      if (this.isBeingResized !== true) {
        this.hideResizeHandle();
      }
    },
    attachResize: function() {
      var domElement = (this.options.elementSelector === null) ? this.view.$el.get(0) : this.view.$(this.options.elementSelector).get(0),
          that = this;
      interact(domElement).resizable({
        //axis: 'y',
        edges: {
          top: false,
          left: false,
          right: false,
          bottom: (typeof this.options.resizeHandleSelector === 'string') ? this.view.$(this.options.resizeHandleSelector).get(0) : this.options.resizeHandleSelector,
        },
      }).on('resizestart', function(event) {
        that.isBeingResized = true;
        that.$el.addClass('mailpoet_resize_active');
      }).on('resizemove', function(event) {
        var currentLength = parseFloat(that.view.model.get(that.options.modelField)),
          newLength = currentLength + that.options.transformationFunction(event.dy);

        if (newLength < that.options.minLength) newLength = that.options.minLength;

        that.view.model.set(that.options.modelField, newLength + 'px');
      }).on('resizeend', function(event) {
        that.isBeingResized = null;
        that.$el.removeClass('mailpoet_resize_active');
      });
    },
    showResizeHandle: function() {
      if (typeof this.options.resizeHandleSelector === 'string') {
        this.view.$(this.options.resizeHandleSelector).removeClass('mailpoet_hidden');
      }
    },
    hideResizeHandle: function() {
      if (typeof this.options.resizeHandleSelector === 'string') {
        this.view.$(this.options.resizeHandleSelector).addClass('mailpoet_hidden');
      }
    },
  });
});
