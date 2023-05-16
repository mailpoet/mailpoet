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
import 'newsletter_editor/ported_blocks/text';

// Force set config to APP
import { App } from 'newsletter_editor/App';
import { ConfigComponent } from 'newsletter_editor/components/config'; // side effect - registers block

window.addEventListener('DOMContentLoaded', () => {
  App.trigger('gutenberg:start', App, { config: {} });

  App.getConfig = ConfigComponent.getConfig;
  App.setConfig = ConfigComponent.setConfig;
  App.setConfig(window.config);
});
