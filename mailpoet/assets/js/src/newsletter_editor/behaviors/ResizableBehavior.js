/**
 * ResizableBehavior
 *
 * Allows resizing elements within a block
 */
import Marionette from 'backbone.marionette';
import BehaviorsLookup from 'newsletter_editor/behaviors/BehaviorsLookup';
import interact from 'interact';

var BL = BehaviorsLookup;

BL.ResizableBehavior = Marionette.Behavior.extend({
  defaults: {
    elementSelector: null,
    resizeHandleSelector: true, // true will use edges of the element itself
    // for blocks that use the default onResize function
    transformationFunction: function transformationFunction(y) {
      return y;
    },
    minLength: 0,
    maxLength: Infinity,
    modelField: 'styles.block.height',
    onResize: function onResize(event) {
      var currentLength = parseFloat(
        this.view.model.get(this.options.modelField),
      );
      var newLength =
        currentLength + this.options.transformationFunction(event.dy);
      newLength = Math.min(
        this.options.maxLength,
        Math.max(this.options.minLength, newLength),
      );
      this.view.model.set(this.options.modelField, newLength + 'px');
    },
  },
  onRender: function onRender() {
    this.attachResize();
    this.view.$el.addClass('mailpoet_resizable_block');
  },
  attachResize: function attachResize() {
    var domElement;
    var that = this;
    if (this.options.elementSelector === null) {
      domElement = this.view.$el.get(0);
    } else {
      domElement = this.view.$(this.options.elementSelector).get(0);
    }
    interact(domElement)
      .resizable({
        // axis: 'y',
        edges: {
          top: false,
          left: false,
          right: false,
          bottom:
            typeof this.options.resizeHandleSelector === 'string'
              ? this.view.$(this.options.resizeHandleSelector).get(0)
              : this.options.resizeHandleSelector,
        },
      })
      .on('resizestart', function resizestart() {
        that.view.model.trigger('startResizing');
        document.activeElement.blur();
      })
      .on('resizemove', function resizemove(event) {
        var onResize = that.options.onResize.bind(that);
        return onResize(event);
      })
      .on('resizeend', function resizeend(event) {
        that.view.model.trigger('stopResizing', event);
        that.$el.removeClass('mailpoet_resize_active');
      });
  },
});
