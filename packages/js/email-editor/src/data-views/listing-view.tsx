/**
 * External dependencies
 */
import { DataViews, View } from '@wordpress/dataviews';
import {
	useEntityRecords,
	Post,
	store as coreStore,
} from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState, useMemo } from '@wordpress/element';
import { Icon, Button } from '@wordpress/components';
import { edit, external, trash } from '@wordpress/icons';
import { BlockPreview } from '@wordpress/block-editor';
import { parse } from '@wordpress/blocks';

export function ListingView() {

	const [ view, setView ] = useState< View >( {
		type: 'table',
		search: '',
		fields: [ 'author', 'status', 'date' ],
		filters: [],
		page: 1,
		perPage: 10,
		sort: {
			field: 'date',
			direction: 'desc',
		},
		titleField: 'title',
		showTitle: true,
		mediaField: 'featured_media',
	} );

	const queryArgs = useMemo( () => {
		const filters = {};
		view.filters.forEach( ( filter ) => {
			if ( filter.field === 'status' && filter.operator === 'isAny' ) {
				filters.status = filter.value;
			}
			if ( filter.field === 'author' && filter.operator === 'is' ) {
				filters.author = filter.value;
			}
		} );
		return {
			status: 'any',
			per_page: view.perPage,
			page: view.page,
			_embed: 'author',
			order: view.sort?.direction,
			orderby: view.sort?.field,
			search: view.search,
			...filters,
		};
	}, [ view ] );

	const { records } = useEntityRecords(
		'postType',
		'mailpoet_email',
		queryArgs
	);
	const { totalRecords } = useSelect(
		( select ) => {
			return {
				totalRecords: select( coreStore ).getEntityRecordsTotalItems(
					'postType',
					'mailpoet_email',
					queryArgs
				),
			};
		},
		[ queryArgs ]
	);

	const fields = [
		{
			id: 'title',
			label: 'Title',
			enableHiding: false,
			render: ( item ) => {
				return item.item.title.raw;
			},
			Edit: 'text',
		},
		{
			id: 'featured_media',
			label: 'Featured Media',
			enableHiding: true,
			render: ( item ) => {
				return <BlockPreview blocks={ parse(item.item.content.raw)} viewportWidth={ 900 } minHeight={ 300 }/>;
			}
		},
		{
			id: 'id',
			label: 'Id',
			enableHiding: false,
		},
		{
			id: 'author',
			label: 'Author',
			enableHiding: true,
			render: ( item ) => {
				const author = item.item._embedded?.author?.[ 0 ] || null;
				const avatarUrl = author?.avatar_urls?.[ 24 ] || null;
				if ( ! author ) return '-';
				return (
					<>
						{ avatarUrl && (
							<>
								<img
									src={ avatarUrl }
									alt={ author.name }
									style={ {
										width: '24px',
										height: '24px',
										borderRadius: '14px',
										border: '1px solid #ddd',
									} }
								/>
								&nbsp;
							</>
						) }
						{ author.name }
					</>
				);
			},
			filterBy: {
				operators: [ 'is' ],
			},
			elements: [
				// @todo Here we need to list all the authors from DB
				{ value: 1, label: 'Admin' },
				{ value: 2, label: 'User' },
			],
		},
		{
			id: 'status',
			label: 'Status',
			enableHiding: true,
			filterBy: {
				operators: [ 'isAny' ],
			},
			elements: [
				{ value: 'draft', label: 'Draft' },
				{ value: 'sent', label: 'Sent' },
				{ value: 'active', label: 'Active' },
			],
		},
		{
			id: 'date',
			label: 'Date',
			enableHiding: false,
			render: ( { item } ) => {
				const date = new Date( item.date );
				return <time>{ date.toLocaleString() }</time>;
			},
		},
	];

	const actions = [
		{
			id: 'edit',
			label: 'Edit',
			icon: <Icon icon={ edit } />,
			supportsBulk: false,
			callback: ( items ) => {
				window.location.href = `/wp-admin/post.php?post=${ items[ 0 ].id }&action=edit`;
			},
			isPrimary: true,
		},
		{
			id: 'preview-tab',
			label: 'Preview in a new tab',
			icon: <Icon icon={external}/>,
			supportsBulk: false,
			callback: (items) => {
				window
					.open(items[0].mailpoet_data.preview_url, '_blank')
					.focus();
			},
		},
		{
			id: 'delete',
			label: 'Delete',
			icon: <Icon icon={ trash } />,
			supportsBulk: true,
			RenderModal: ( { items, closeModal, onActionPerformed } ) => (
				<div>
					<p>Are you sure you want to delete { items.length } item(s)?</p>
					<Button
						variant="primary"
						onClick={() => {
							console.log( 'Deleting items:', items );
							onActionPerformed();
							closeModal();
						}}
					>
						Confirm Delete
					</Button>
				</div>
			)
		},
	];

	const form = {
		type: 'panel',
		fields: [ 'title' ],
	};

	console.log( 'emails', records );

	return (
		<DataViews
			view={ view }
			form={ form }
			actions={ actions }
			onChangeView={ setView }
			fields={ fields }
			data={ records ?? [] }
			paginationInfo={ {
				totalItems: totalRecords,
				totalPages: Math.ceil( totalRecords / view.perPage ),
			} }
			defaultLayouts={ {
				table: {
					showMedia: false,
				},
				grid: {
					showMedia: true,
				},
				list: {
					showMedia: true,
				},
			} }
			isItemClickable={ () => false } // Click on the row
			getItemId={ ( item: Post ) => item.id.toString() }
		/>
	);
}
