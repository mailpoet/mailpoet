/**
 * WordPress dependencies
 */
import {
	// @ts-expect-error No types for this exist yet.
	privateApis as blockEditorPrivateApis,
} from '@wordpress/block-editor';
import { privateApis as componentsPrivateApis } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { unlock } from './lock-unlock';

/**
 * We use the experimental block canvas to render the block editor's canvas.
 * Currently this is needed because we use contentRef property which is not available in the stable BlockCanvas
 * The property is used for handling clicks for selecting block to edit and to display modal for switching between email and template.
 */
const { ExperimentalBlockCanvas: BlockCanvas } = unlock(
	blockEditorPrivateApis
);

/**
 * Tabs are used in the right sidebar header to switch between Email and Block settings.
 * Tabs should be close to stablization https://github.com/WordPress/gutenberg/pull/61072
 */
const { Tabs } = unlock( componentsPrivateApis );

/**
 * We need the following selectors from core store to fetch block patterns for the email post type.
 * @param select - select function from the core store.
 */
const unlockPatternsRelatedSelectorsFromCoreStore = ( select ) => {
	const { hasFinishedResolution, getBlockPatternsForPostType } = unlock(
		select( coreStore )
	);
	return { hasFinishedResolution, getBlockPatternsForPostType };
};

/**
 * Selector getEnabledClientIdsTree for block-editor store is used to find nearest editable block to select on click in
 * useSelectNearestEditableBlock
 * We copied useSelectNearestEditableBlock from Gutenberg.
 * @param selectHook - useSelect call from the block editor store `useSelect( blockEditorStore ).
 */
const unlockGetEnabledClientIdsTree = ( selectHook ) => {
	const { getEnabledClientIdsTree } = unlock( selectHook );
	return getEnabledClientIdsTree;
};

/**
 * We use the ColorPanel component from the block editor to render the color panel in the style settings sidebar.
 */
const { ColorPanel: StylesColorPanel } = unlock( blockEditorPrivateApis );

export {
	BlockCanvas,
	Tabs,
	StylesColorPanel,
	unlockPatternsRelatedSelectorsFromCoreStore,
	unlockGetEnabledClientIdsTree,
};
