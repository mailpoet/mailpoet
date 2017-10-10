/**
 * DraggableBehavior
 *
 * Allows View instances to be draggable.
 * Part of the drag&drop behavior.
 */
define([
  'backbone.marionette',
  'underscore',
  'jquery',
  'newsletter_editor/behaviors/BehaviorsLookup',
  'interact'
], function (Marionette, _, jQuery, BehaviorsLookup, interact) {
  var BL = BehaviorsLookup;

  BL.DraggableBehavior = Marionette.Behavior.extend({
    defaults: {
      cloneOriginal: false,
      hideOriginal: false,
      ignoreSelector: '.mailpoet_ignore_drag, .mailpoet_ignore_drag *',
      onDragSubstituteBy: undefined,
      /**
       * Constructs a model that will be passed to the receiver on drop
       *
       * @return Backbone.Model A model that will be passed to the receiver
       */
      getDropModel: function () {
        throw "Missing 'drop' function for DraggableBehavior";
      },

      onDrop: function () {},
      testAttachToInstance: function () { return true; }
    },
    onRender: function () {
      var that = this;
      var interactable;

      // Give instances more control over whether Draggable should be applied
      if (!this.options.testAttachToInstance(this.view.model, this.view)) return;

      interactable = interact(this.$el.get(0), {
        ignoreFrom: this.options.ignoreSelector
      }).draggable({
        // allow dragging of multple elements at the same time
        max: Infinity,

        // Scroll when dragging near edges of a window
        autoScroll: true,

        onstart: function (startEvent) {
          var event = startEvent;
          var centerXOffset;
          var centerYOffset;
          var tempClone;
          var clone;
          var $clone;

          if (that.options.cloneOriginal === true) {
            // Use substitution instead of a clone
            tempClone = (_.isFunction(that.options.onDragSubstituteBy)) ? that.options.onDragSubstituteBy(that) : undefined;
            // Or use a clone
            clone = tempClone || event.target.cloneNode(true);
            jQuery(event.target);
            $clone = jQuery(clone);

            $clone.addClass('mailpoet_droppable_active');
            $clone.css('position', 'absolute');
            $clone.css('top', 0);
            $clone.css('left', 0);
            document.body.appendChild(clone);

            // Position the clone over the target element with a slight
            // offset to center the clone under the mouse cursor.
            // Accurate dimensions can only be taken after insertion to document
            centerXOffset = $clone.width() / 2;
            centerYOffset = $clone.height() / 2;
            $clone.css('top', event.pageY - centerYOffset);
            $clone.css('left', event.pageX - centerXOffset);

            event.interaction.element = clone;


            if (that.options.hideOriginal === true) {
              that.view.$el.addClass('mailpoet_hidden');
            }
          }

        },
        // call this function on every dragmove event
        onmove: function (event) {
          var target = event.target;
          // keep the dragged position in the data-x/data-y attributes
          var x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
          var y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

          // translate the element
          target.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
          target.style.webkitTransform = target.style.transform;

          // update the posiion attributes
          target.setAttribute('data-x', x);
          target.setAttribute('data-y', y);
        },
        onend: function (event) {
          var target = event.target;
          target.style.transform = '';
          target.style.webkitTransform = target.style.transform;
          target.removeAttribute('data-x');
          target.removeAttribute('data-y');
          jQuery(event.interaction.element).addClass('mailpoet_droppable_active');

          if (that.options.cloneOriginal === true) {
            jQuery(target).remove();

            if (that.options.hideOriginal === true) {
              that.view.$el.removeClass('mailpoet_hidden');
            }
          }
        }
      })
      .preventDefault('auto')
      .styleCursor(false)
      .actionChecker(function (pointer, event, action) {
        // Disable dragging with right click
        if (event.button !== 0) {
          return null;
        }

        return action;
      });

      if (this.options.drop !== undefined) {
        interactable.getDropModel = this.options.drop;
      } else {
        interactable.getDropModel = this.view.getDropFunc();
      }
      interactable.onDrop = function (opts) {
        var options = opts;
        if (_.isObject(options)) {
          // Inject Draggable behavior if possible
          options.dragBehavior = that;
        }
        // Delegate to view's event handler
        that.options.onDrop.apply(that, [options]);
      };
    }
  });
});
