/**
 * WordPress dependencies
 */
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';

export const { lock, unlock } =
  __dangerousOptInToUnstableAPIsOnlyForCoreModules(
    'I know using unstable features means my theme or plugin will inevitably break in the next version of WordPress.',
    '@wordpress/edit-post', // The module name must be in the list of allowed, so for now I used the package name of the post editor
  );
