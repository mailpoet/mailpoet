/**
 * ResizableBehavior
 *
 * Allows resizing elements within a block
 */
define([
  'backbone.marionette',
  'newsletter_editor/behaviors/BehaviorsLookup',
  'interact'
], function (Marionette, BehaviorsLookup, interact) {
  var BL = BehaviorsLookup;

  BL.ResizableBehavior = Marionette.Behavior.extend({
    defaults: {
      elementSelector: null,
      resizeHandleSelector: true, // true will use edges of the element itself
      transformationFunction: function (y) { return y; }, // for blocks that use the default onResize function
      minLength: 0,
      maxLength: Infinity,
      modelField: 'styles.block.height',
      onResize: function (event) {
        var currentLength = parseFloat(this.view.model.get(this.options.modelField));
        var newLength = currentLength + event.y;
        newLength = Math.min(this.options.maxLength, Math.max(this.options.minLength, newLength));
        this.view.model.set(this.options.modelField, newLength + 'px');
      }
    },
    events: {
      mouseenter: 'showResizeHandle',
      mouseleave: 'hideResizeHandle'
    },
    onRender: function () {
      this.attachResize();

      if (this.isBeingResized !== true) {
        this.hideResizeHandle();
      }
    },
    attachResize: function () {
      var domElement = (this.options.elementSelector === null) ? this.view.$el.get(0) : this.view.$(this.options.elementSelector).get(0);
      var that = this;
      interact(domElement).resizable({
        // axis: 'y',
        edges: {
          top: false,
          left: false,
          right: false,
          bottom: (typeof this.options.resizeHandleSelector === 'string') ? this.view.$(this.options.resizeHandleSelector).get(0) : this.options.resizeHandleSelector
        }
      })
      .on('resizestart', function () {
        that.isBeingResized = true;
        that.$el.addClass('mailpoet_resize_active');
      })
      .on('resizemove', function (event) {
        var onResize = that.options.onResize.bind(that);
        return onResize(event);
      })
      .on('resizeend', function () {
        that.isBeingResized = null;
        that.$el.removeClass('mailpoet_resize_active');
      });
    },
    showResizeHandle: function () {
      if (typeof this.options.resizeHandleSelector === 'string') {
        this.view.$(this.options.resizeHandleSelector).removeClass('mailpoet_hidden');
      }
    },
    hideResizeHandle: function () {
      if (typeof this.options.resizeHandleSelector === 'string') {
        this.view.$(this.options.resizeHandleSelector).addClass('mailpoet_hidden');
      }
    }
  });
});
