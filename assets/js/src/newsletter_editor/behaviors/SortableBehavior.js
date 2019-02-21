/**
 * SortableBehavior
 *
 * Allows sorting elements within a collection
 */
import Marionette from 'backbone.marionette';
import _ from 'underscore';
import BehaviorsLookup from 'newsletter_editor/behaviors/BehaviorsLookup'; // eslint-disable-line func-names

  var BL = BehaviorsLookup;

  BL.SortableBehavior = Marionette.Behavior.extend({
    onRender: function () { // eslint-disable-line func-names
      var collection = this.view.collection;

      if (_.isFunction(this.$el.sortable)) {
        this.$el.sortable({
          cursor: 'move',
          start: function (event, ui) { // eslint-disable-line func-names
            ui.item.data('previousIndex', ui.item.index());
          },
          end: function (event, ui) { // eslint-disable-line func-names
            ui.item.removeData('previousIndex');
          },
          update: function (event, ui) { // eslint-disable-line func-names
            var previousIndex = ui.item.data('previousIndex');
            var newIndex = ui.item.index();
            var model = collection.at(previousIndex);

            // Replicate DOM changes. Move target model to a new position
            // within the collection
            collection.remove(model);
            collection.add(model, { at: newIndex });
          },
          items: this.options.items
        });
      }
    }
  });
