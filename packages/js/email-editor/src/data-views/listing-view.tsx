/**
 * External dependencies
 */
import { DataViews } from '@wordpress/dataviews';
import { useEntityRecords, Post, store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState, useMemo } from '@wordpress/element';

export function ListingView() {
	const [view, setView] = useState( {
		type: 'list',
		search: '',
		fields: ['id', 'date'],
		page: 1,
		perPage: 10,
		sort: {
			field: 'date',
			direction: 'desc',
		},
		titleField: 'title',
		showTitle: true,
		filters: [],
	} );

	const queryArgs = useMemo( () => {
		return {
			status: 'any',
			per_page: view.perPage,
			page: view.page,
			_embed: 'author',
			order: view.sort?.direction,
			orderby: view.sort?.field,
			search: view.search,
		};
	}, [ view ] );

	const { records } = useEntityRecords( 'postType', 'mailpoet_email', queryArgs );
	const { totalRecords } = useSelect( ( select ) => {
		return {
			totalRecords: select( coreStore ).getEntityRecordsTotalItems( 'postType', 'mailpoet_email', queryArgs ),
		}
	}, [ queryArgs ] );

	const fields = [
		{
			id: 'title',
			label: 'Title',
			enableHiding: false,
			render: (item) => {
				return item.item.title.raw;
			}
		},
		{
			id: 'id',
			label: 'Id',
			enableHiding: false,
		},
		{
			id: 'date',
			label: 'Date',
			enableHiding: false,
			render: ( { item } ) => {
				return <time>{ item.date }</time>;
			},
		},
	];

	if ( records === null || totalRecords === null ) {
		return null;
	}

	console.log( 'emails', records );

	return (
		<DataViews
			view={ view }
			// @ts-expect-error Weird error
			onChangeView={ setView }
			fields={ fields }
			data={ records }
			paginationInfo={ {
				totalItems: totalRecords,
				totalPages: Math.ceil( totalRecords / view.perPage ),
			} }
			defaultLayouts={ {} }
			getItemId={ ( item: Post ) => item.id.toString() }
			actions={ [] }
		/>
	);
}
