/**
 * External dependencies
 */
import { DataViews } from '@wordpress/dataviews';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

export function ListingView() {
	const { emails } = useSelect( ( select ) => {
		return {
			emails: select( coreStore ).getEntityRecords(
				'postType',
				'mailpoet_email',
				{
					status: 'any',
					per_page: -1,
				}
			)
		};
	}, [] );

	const view = {
		type: 'list',
		search: '',
		fields: ['id', 'date'],
		page: 1,
		perPage: 10,
		sort: {
			field: 'title',
		},
		titleField: 'title',
		showTitle: true,
	};

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

	if ( ! emails ) {
		return null;
	}

	console.log( 'emails', emails );

	return (
		<DataViews
			view={ view }
			onChangeView={ () => {} }
			fields={ fields }
			data={ emails }
			paginationInfo={ {
				totalItems: 2,
				totalPages: 1,
			} }
			defaultLayouts={ {} }
			getItemId={ ( item ) => item.id.toString() }
			actions={ [] }
		/>
	);
}
