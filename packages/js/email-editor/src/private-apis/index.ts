import { unlock } from './lock-unlock';
import {
	// @ts-expect-error No types for this exist yet.
	privateApis as blockEditorPrivateApis,
} from '@wordpress/block-editor';
import { privateApis as componentsPrivateApis } from '@wordpress/components';

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

export { BlockCanvas, Tabs };
