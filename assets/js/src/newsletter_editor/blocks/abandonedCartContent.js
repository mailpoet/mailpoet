/**
 * Abandoned cart content block.
 *
 * This block depends on blocks.divider for block model and
 * block settings view.
 */
import App from 'newsletter_editor/App';
import BaseBlock from 'newsletter_editor/blocks/base';

var Module = {};
var base = BaseBlock;

Module.AbandonedCartContentBlockWidgetView = base.WidgetView.extend({
  className: base.WidgetView.prototype.className + ' mailpoet_droppable_layout_block',
  id: 'automation_editor_block_abandoned_cart_content',
  getTemplate: function getTemplate() { return window.templates.abandonedCartContentInsertion; },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function drop() {
        return new Module.AbandonedCartContentBlockModel({}, { parse: true });
      },
    },
  },
});

App.on('before:start', function beforeStartApp(BeforeStartApp) {
  if (!window.mailpoet_woocommerce_active) {
    return;
  }
  BeforeStartApp.registerWidget({
    name: 'abandonedCartContent',
    widgetView: Module.AbandonedCartContentBlockWidgetView,
    priority: 99,
  });
});

export default Module;
