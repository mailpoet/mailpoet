import App from 'newsletter_editor/App';
import BaseBlock from 'newsletter_editor/blocks/base';

const Module = {};
const base = BaseBlock;

Module.BlockModel = base.BlockModel.extend({});

Module.BlockView = base.BlockView.extend({
  className: 'mailpoet_block mailpoet_fallback_block mailpoet_droppable_block',
  getTemplate: function getTemplate() {
    return window.templates.unknownBlockFallbackBlock;
  },
  onRender: function onRender() {
    this.toolsView = new Module.BlockToolsView({
      model: this.model,
      tools: {
        settings: false,
        duplicate: false,
      },
    });
    setImmediate(() => {
      this.showChildView('toolsRegion', this.toolsView);
    });
  },
  templateContext() {
    return {
      blockType: this.model.get('type'),
    };
  },
});

Module.WidgetView = base.WidgetView.extend({
  id: 'automation_editor_block_fallback',
  getTemplate: function getTemplate() {
    return window.templates.unknownBlockFallbackInsertion;
  },
  behaviors: {
    DraggableBehavior: {
      cloneOriginal: true,
      drop: function drop() {
        return new Module.BlockModel();
      },
    },
  },
});

Module.BlockToolsView = base.BlockToolsView.extend({});

App.on('before:start', function beforeAppStart(BeforeStartApp) {
  BeforeStartApp.registerBlockType('unknownBlockFallback', {
    blockModel: Module.BlockModel,
    blockView: Module.BlockView,
  });
});

export default Module;
