/**
 * External dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ListingView } from './data-views/listing-view';

function DataViews() {
	return (
		<>
			<h1>Email Data Views</h1>
			<ListingView />
		</>
	);
}

export function initialize( elementId: string ) {
	const container = document.getElementById( elementId );
	if ( ! container ) {
		return;
	}
	const root = createRoot( container );
	root.render( <DataViews /> );
}
