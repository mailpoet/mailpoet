// Initialize Editor dependencies that have side effect (meaning they
// not only define module but also modify/register something on load).

// This is to avoid undefined import order & messy WebPack config.
// Code can be gradually refactored to avoid side effects completely.

// dependencies
import 'sticky-kit'; // side effect - extends jQuery
import 'velocity-animate'; // side effect - assigns to window

// app
import 'newsletter_editor/initializer.jsx'; // side effect - calls Hooks.addAction()
import 'newsletter_editor/App'; // side effect - assigns to window

// components
import 'newsletter_editor/components/config.jsx'; // side effect - calls App.on()
import 'newsletter_editor/components/styles.js'; // side effect - calls App.on()
import 'newsletter_editor/components/sidebar.js'; // side effect - calls App.on()
import 'newsletter_editor/components/content.js'; // side effect - calls App.on()
import 'newsletter_editor/components/heading.js'; // side effect - calls App.on()
import 'newsletter_editor/components/history.js'; // side effect - calls App.on()
import 'newsletter_editor/components/save.js'; // side effect - calls App.on()
import 'newsletter_editor/components/communication.js'; // side effect - calls App.on()

// behaviors
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

// blocks
import 'newsletter_editor/blocks/container.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/button.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/image.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/divider.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/text.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/spacer.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/footer.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/header.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/automatedLatestContent.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/automatedLatestContentLayout.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/posts.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/products.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/abandonedCartContent.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/social.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/woocommerceContent.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/woocommerceHeading.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/unknownBlockFallback.js'; // side effect - calls App.on()
import 'newsletter_editor/blocks/coupon'; // side effect - calls App.on()
