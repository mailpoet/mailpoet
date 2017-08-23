/**
 * SortableBehavior
 *
 * Allows sorting elements within a collection
 */
define([
    'backbone.marionette',
    'underscore',
    'newsletter_editor/behaviors/BehaviorsLookup'
  ], function(Marionette, _, BehaviorsLookup) {
  var BL = BehaviorsLookup;

  BL.SortableBehavior = Marionette.Behavior.extend({
    onRender: function() {
      var collection = this.view.collection;

      if (_.isFunction(this.$el.sortable)) {
        this.$el.sortable({
          cursor: 'move',
          start: function(event, ui) {
            ui.item.data('previousIndex', ui.item.index());
          },
          end: function(event, ui) {
            ui.item.removeData('previousIndex');
          },
          update: function(event, ui) {
            var previousIndex = ui.item.data('previousIndex'),
              newIndex = ui.item.index(),
              model = collection.at(previousIndex);

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
});
