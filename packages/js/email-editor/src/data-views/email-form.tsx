/**
 * External dependencies
 */
import { DataForm } from '@wordpress/dataviews';
import { useEntityRecord } from '@wordpress/core-data';
import { Post } from '@wordpress/core-data/build-types/entity-types/post';

export function EmailForm( { email } ) {
	const { editedRecord, edit } = useEntityRecord(
		'postType',
		'mailpoet_email',
		email.id
	);
	return (
		<div>
			{ /* @ts-expect-error No types available for this */ }
			<h3>Quick Edit - { editedRecord.title }</h3>
			<DataForm
				data={ editedRecord }
				fields={ [
					{
						id: 'title',
						label: 'Title',
						type: 'text',
						getValue: ( item: { item: Post } ) => {
							return item.item.title;
						},
					},
					{
						id: 'subject',
						label: 'Subject',
						type: 'text',
						getValue: ( item: {
							item: { mailpoet_data: { subject: string } };
						} ) => {
							return item.item.mailpoet_data.subject;
						},
					},
					{
						id: 'status',
						label: 'Status',
						type: 'text',
						elements: [
							{ value: 'draft', label: 'Draft' },
							{ value: 'sent', label: 'Sent' },
							{ value: 'active', label: 'Active' },
						],
					},
				] }
				form={ {
					fields: [ 'title', 'subject', 'status' ],
					labelPosition: undefined,
					type: undefined,
				} }
				onChange={ ( changedData ) => edit( changedData ) }
			/>
		</div>
	);
}
