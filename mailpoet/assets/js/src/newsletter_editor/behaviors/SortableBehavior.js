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
  // eslint-disable-next-line func-names
  onRender: function () {
    var collection = this.view.collection;

    if (_.isFunction(this.$el.sortable)) {
      this.$el.sortable({
        cursor: 'move',
        // eslint-disable-next-line func-names
        start: function (event, ui) {
          ui.item.data('previousIndex', ui.item.index());
        },
        // eslint-disable-next-line func-names
        end: function (event, ui) {
          ui.item.removeData('previousIndex');
        },
        // eslint-disable-next-line func-names
        update: function (event, ui) {
          var previousIndex = ui.item.data('previousIndex');
          var newIndex = ui.item.index();
          var model = collection.at(previousIndex);

          // Replicate DOM changes. Move target model to a new position
          // within the collection
          collection.remove(model);
          collection.add(model, { at: newIndex });
        },
        items: this.options.items,
      });
    }
  },
});
