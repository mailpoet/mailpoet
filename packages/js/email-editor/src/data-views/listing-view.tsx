/**
 * External dependencies
 */
import { DataViews, View } from '@wordpress/dataviews';
import { useEntityRecords, Post, store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useState, useMemo } from '@wordpress/element';
import { Icon } from '@wordpress/components';
import { edit, external } from '@wordpress/icons';

export function ListingView() {
	const [view, setView] = useState( {
		type: 'table',
		search: '',
		fields: ['author', 'status', 'date'],
		filters: [
			{ field: 'author', operator: 'is', value: 2 },
			{ field: 'status', operator: 'isAny', value: [ 'sent', 'draft' ] },
		],
		page: 1,
		perPage: 10,
		sort: {
			field: 'date',
			direction: 'desc',
		},
		titleField: 'title',
		showTitle: true,
	} as View ) ;

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
			},
			Edit: 'text',
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
			render: (item) => {
				const author = item.item._embedded?.author?.[0] || null;
				console.log(item.item._embedded)
				const avatarUrl = author?.avatar_urls?.[24] || null;
				if (!author) return '-';
				return <>
					{ avatarUrl && <img src={ avatarUrl	} alt={author.name} style={{width:'24px', height:'24px', borderRadius: '12px'}}/> } { author.name }
				</>
			},
		},
		{
			id: 'status',
			label: 'Status',
			enableHiding: true,
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

	const actions = [
		{
			id: 'edit',
			label: 'Edit',
			icon: <Icon icon={ edit } />,
			supportsBulk: false,
			callback: ( items ) => {
				window.location.href = `/wp-admin/post.php?post=${ items[0].id }&action=edit`;
			},
			isPrimary: true,
		},
		{
			id: 'preview-tab',
			label: 'Preview in a new tab',
			icon: <Icon icon={ external } />,
			supportsBulk: false,
			callback: ( items ) => {
				window.open(items[0].mailpoet_data.preview_url, '_blank').focus();
			}
		}
	];

	if ( records === null || totalRecords === null ) {
		return null;
	}

	console.log( 'emails', records );

	return (
		<DataViews
			view={ view }
			actions={ actions }
			onChangeView={ setView }
			fields={ fields }
			data={ records }
			paginationInfo={ {
				totalItems: totalRecords,
				totalPages: Math.ceil( totalRecords / view.perPage ),
			} }
			defaultLayouts={{
				table: {
					showMedia: false,
				},
				grid: {
					showMedia: true,
				},
				list: {
					showMedia: true,
				}
			}}
			getItemId={ ( item: Post ) => item.id.toString() }
		/>
	);
}
