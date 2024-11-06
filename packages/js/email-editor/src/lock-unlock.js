/**
 * WordPress dependencies
 */
import { __dangerousOptInToUnstableAPIsOnlyForCoreModules } from '@wordpress/private-apis';

export const { lock, unlock } =
	__dangerousOptInToUnstableAPIsOnlyForCoreModules(
		'I acknowledge private features are not for use in themes or plugins and doing so will break in the next version of WordPress.',
		'@wordpress/edit-post' // The module name must be in the list of allowed, so for now I used the package name of the post editor
	);
