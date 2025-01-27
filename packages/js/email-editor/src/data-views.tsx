/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ListingView } from './data-views/listing-view';
import { createStore } from './store';
import { initBlocks } from './blocks';

function DataViews() {
	return (
		<>
			<h1>Email Dataviews Demo</h1>
			<ListingView />
		</>
	);
}

export function initialize( elementId: string ) {
	const container = document.getElementById( elementId );
	if ( ! container ) {
		return;
	}
	createStore();
	initBlocks();
	const root = createRoot( container );
	root.render( <DataViews /> );
}
