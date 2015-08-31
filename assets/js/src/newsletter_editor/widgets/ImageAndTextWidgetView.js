/**
 * A sample implementation of template widgets.
 * A draggable widget, on drop creates a container with (image|text) block.
 */
ImageAndTextTemplateWidgetView = EditorApplication.module('blocks.base').WidgetView.extend({
  getTemplate: function() { return templates.imageAndTextInsertion; },
  className: 'mailpoet_droppable_block mailpoet_droppable_widget',
  behaviors: {
    DraggableBehavior: {
      drop: function() {
        return new (EditorApplication.getBlockTypeModel('container'))({
          type: 'container',
          orientation: 'horizontal',
          blocks: [
            {
              type: 'image',
            },
            {
              type: 'text',
              text: 'Some random text',
            },
          ],
        }, {parse: true});
      },
    }
  },
});
