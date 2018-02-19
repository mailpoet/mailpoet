/**
 * ResizableBehavior
 *
 * Allows resizing elements within a block
 */
define([
  'backbone.marionette',
  'newsletter_editor/behaviors/BehaviorsLookup',
  'interact'
], function (Marionette, BehaviorsLookup, interact) { // eslint-disable-line func-names
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
      onResize: function (event) { // eslint-disable-line func-names
        var currentLength = parseFloat(this.view.model.get(this.options.modelField));
        var newLength = currentLength + this.options.transformationFunction(event.dy);
        newLength = Math.min(this.options.maxLength, Math.max(this.options.minLength, newLength));
        this.view.model.set(this.options.modelField, newLength + 'px');
      }
    },
    events: {
      mouseenter: 'showResizeHandle',
      mouseleave: 'hideResizeHandle'
    },
    onRender: function () { // eslint-disable-line func-names
      this.attachResize();

      if (this.isBeingResized !== true) {
        this.hideResizeHandle();
      }
    },
    attachResize: function () { // eslint-disable-line func-names
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
          bottom: (typeof this.options.resizeHandleSelector === 'string') ? this.view.$(this.options.resizeHandleSelector).get(0) : this.options.resizeHandleSelector
        }
      })
      .on('resizestart', function () { // eslint-disable-line func-names
        that.isBeingResized = true;
        that.$el.addClass('mailpoet_resize_active');
      }).on('resizemove', function (event) { // eslint-disable-line func-names
        var onResize = that.options.onResize.bind(that);
        return onResize(event);
      })
      .on('resizeend', function () { // eslint-disable-line func-names
        that.isBeingResized = null;
        that.$el.removeClass('mailpoet_resize_active');
      });
    },
    showResizeHandle: function () { // eslint-disable-line func-names
      if (typeof this.options.resizeHandleSelector === 'string') {
        this.view.$(this.options.resizeHandleSelector).removeClass('mailpoet_hidden');
      }
    },
    hideResizeHandle: function () { // eslint-disable-line func-names
      if (typeof this.options.resizeHandleSelector === 'string') {
        this.view.$(this.options.resizeHandleSelector).addClass('mailpoet_hidden');
      }
    }
  });
});
