// Initialize Editor dependencies that have side effect (meaning they
// not only define module but also modify/register something on load).

// This is to avoid undefined import order & messy WebPack config.
// Code can be gradually refactored to avoid side effects completely.

// dependencies
import 'sticky-kit'; // side effect - extends jQuery
import 'velocity-animate'; // side effect - assigns to window

// app
import 'newsletter-editor/initializer.jsx'; // side effect - calls Hooks.addAction()
import 'newsletter-editor/app'; // side effect - assigns to window

// components
import 'newsletter-editor/components/config.jsx'; // side effect - calls App.on()
import 'newsletter-editor/components/styles.js'; // side effect - calls App.on()
import 'newsletter-editor/components/sidebar.tsx'; // side effect - calls App.on()
import 'newsletter-editor/components/content.js'; // side effect - calls App.on()
import 'newsletter-editor/components/heading.js'; // side effect - calls App.on()
import 'newsletter-editor/components/history.js'; // side effect - calls App.on()
import 'newsletter-editor/components/save.js'; // side effect - calls App.on()
import 'newsletter-editor/components/communication.js'; // side effect - calls App.on()

// behaviors
import 'newsletter-editor/behaviors/behaviors-lookup.js'; // side effect - assings to window and Marionette
import 'newsletter-editor/behaviors/color-picker-behavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter-editor/behaviors/container-drop-zone-behavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter-editor/behaviors/draggable-behavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter-editor/behaviors/highlight-editing-behavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter-editor/behaviors/media-manager-behavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter-editor/behaviors/resizable-behavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter-editor/behaviors/sortable-behavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter-editor/behaviors/show-settings-behavior.js'; // side effect - assigns to BehaviorsLookup
import 'newsletter-editor/behaviors/text-editor-behavior'; // side effect - assigns to BehaviorsLookup
import 'newsletter-editor/behaviors/woo-commerce-styles-behavior.js'; // side effect - assigns to BehaviorsLookup

// blocks
import 'newsletter-editor/blocks/container.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/button.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/image.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/divider.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/text.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/spacer.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/footer.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/header.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/automated-latest-content.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/automated-latest-content-layout.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/posts.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/products.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/abandoned-cart-content.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/social.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/woocommerce-content.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/woocommerce-heading.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/unknown-block-fallback.js'; // side effect - calls App.on()
import 'newsletter-editor/blocks/coupon'; // side effect - calls App.on()

// register strings for native translation with @wordpress/i18n
import { registerTranslations } from 'common';

registerTranslations();
