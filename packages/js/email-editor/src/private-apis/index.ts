import { unlock } from './lock-unlock';
import {
	// @ts-expect-error No types for this exist yet.
	privateApis as blockEditorPrivateApis,
} from '@wordpress/block-editor';

/**
 * We use the experimental block canvas to render the block editor's canvas.
 * Currently this is needed because we use contentRef property which is not available in the stable BlockCanvas
 * The property is used for handling clicks for selecting block to edit and to display modal for switching between email and template.
 */
const { ExperimentalBlockCanvas: BlockCanvas } = unlock(
	blockEditorPrivateApis
);

export { BlockCanvas };
