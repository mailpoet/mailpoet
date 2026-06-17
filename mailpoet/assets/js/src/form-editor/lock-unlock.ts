/**
 * WordPress dependencies
 */
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';

export const { lock, unlock } =
  __dangerousOptInToUnstableAPIsOnlyForCoreModules(
    'I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.',
    '@wordpress/block-directory', // The module name must be allowed and not already registered by WordPress 6.9.
  );
