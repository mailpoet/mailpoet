// Import Behaviours for legacy blocks
import 'newsletter_editor/behaviors/BehaviorsLookup.js'; // side effect - assings to window and Marionette
import 'newsletter_editor/behaviors/ColorPickerBehavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter_editor/behaviors/ContainerDropZoneBehavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter_editor/behaviors/DraggableBehavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter_editor/behaviors/HighlightEditingBehavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter_editor/behaviors/MediaManagerBehavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter_editor/behaviors/ResizableBehavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter_editor/behaviors/SortableBehavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter_editor/behaviors/ShowSettingsBehavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter_editor/behaviors/TextEditorBehavior'; // side effect - assigns to BehaviorsLookup
import 'newsletter_editor/behaviors/WooCommerceStylesBehavior.js'; // side effect - assigns to BehaviorsLookup

// Register blocks
import 'newsletter_editor/ported_blocks/image/index';
import 'newsletter_editor/ported_blocks/text/index';
import 'newsletter_editor/ported_blocks/button/index';
import 'newsletter_editor/ported_blocks/posts/index';

// Models
import { ContainerBlock } from 'newsletter_editor/blocks/container';
import { DividerBlock } from 'newsletter_editor/blocks/divider';
import { ButtonBlock } from 'newsletter_editor/blocks/button';
import { TextBlock } from 'newsletter_editor/blocks/text';
import { Module as FallbackBlock } from 'newsletter_editor/blocks/unknownBlockFallback';

// Force set config to APP
import { App } from 'newsletter_editor/App';
import { ConfigComponent } from 'newsletter_editor/components/config'; // side effect - registers block

window.addEventListener('DOMContentLoaded', () => {
  App.trigger('gutenberg:start', App, { config: {} });

  App.getConfig = ConfigComponent.getConfig;
  App.setConfig = ConfigComponent.setConfig;
  App.setConfig(window.config);
  App.getAvailableStyles = () =>
    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    ConfigComponent.getConfig().get('availableStyles');
  // Posts block needs internally load submodels to keep data for settings of different parts of the block (butto for read more button etc.)
  App.getBlockTypeModel = (blockType) => {
    if (blockType === 'container') {
      // eslint-disable-next-line @typescript-eslint/no-unsafe-return
      return ContainerBlock.ContainerBlockModel;
    }
    if (blockType === 'divider') {
      // eslint-disable-next-line @typescript-eslint/no-unsafe-return
      return DividerBlock.DividerBlockModel;
    }
    if (blockType === 'button') {
      // eslint-disable-next-line @typescript-eslint/no-unsafe-return
      return ButtonBlock.ButtonBlockModel;
    }
    if (blockType === 'text') {
      // eslint-disable-next-line @typescript-eslint/no-unsafe-return
      return TextBlock.TextBlockModel;
    }
    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    return FallbackBlock.BlockModel;
  };

  App.getBlockTypeView = (blockType) => {
    if (blockType === 'container') {
      // eslint-disable-next-line @typescript-eslint/no-unsafe-return
      return ContainerBlock.ContainerBlockView;
    }
    if (blockType === 'divider') {
      // eslint-disable-next-line @typescript-eslint/no-unsafe-return
      return DividerBlock.DividerBlockView;
    }
    if (blockType === 'button') {
      // eslint-disable-next-line @typescript-eslint/no-unsafe-return
      return ButtonBlock.ButtonBlockView;
    }
    if (blockType === 'text') {
      // eslint-disable-next-line @typescript-eslint/no-unsafe-return
      return TextBlock.TextBlockView;
    }
    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    return FallbackBlock.BlockView;
  };
});
