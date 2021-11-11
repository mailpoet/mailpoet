/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import FrontendBlock from './block';

registerCheckoutBlock({
  metadata,
  component: FrontendBlock,
});
