import { BaseControl, Button } from '@wordpress/components';
import { PersonalizationTagsModal } from './personalization-tags-modal';
import { useCallback, useRef, useState } from '@wordpress/element';
import {
	createTextToHtmlMap,
	getCursorPosition,
	isMatchingComment,
	replacePersonalizationTagsWithHTMLComments,
} from './rich-text-utils';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { storeName } from '../../store';
import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { PersonalizationTagsPopover } from './personalization-tags-popover';

export function RichTextWithButton( {
	label,
	labelSuffix,
	help,
	placeholder,
	attributeName,
} ) {
	const [ mailpoetEmailData ] = useEntityProp(
		'postType',
		'mailpoet_email',
		'mailpoet_data'
	);

	const { updateEmailMailPoetProperty } = useDispatch( storeName );

	const [ selectionRange, setSelectionRange ] = useState( null );
	const [ isModalOpened, setIsModalOpened ] = useState( false );
	const list = useSelect(
		( select ) => select( storeName ).getPersonalizationTagsList(),
		[]
	);

	const richTextRef = useRef( null );

	const handleInsertPersonalizationTag = useCallback(
		( tagName, currentValue, currentSelectionRange ) => {
			// Generate text-to-HTML mapping
			const { mapping } = createTextToHtmlMap( currentValue );
			// Ensure selection range is within bounds
			const start = currentSelectionRange?.start ?? currentValue.length;
			const end = currentSelectionRange?.end ?? currentValue.length;

			// Default values for starting and ending indexes.
			let htmlStart = start;
			let htmlEnd = end;
			// If indexes are not matching a comment, update them
			if ( ! isMatchingComment( currentValue, start, end ) ) {
				htmlStart = mapping[ start ] ?? currentValue.length;
				htmlEnd = mapping[ end ] ?? currentValue.length;
			}

			// Insert the new tag
			const updatedValue =
				currentValue.slice( 0, htmlStart ) +
				`<!--${ tagName }-->` +
				currentValue.slice( htmlEnd );

			// Update the corresponding property
			updateEmailMailPoetProperty( attributeName, updatedValue );

			setSelectionRange( null );
		},
		[ attributeName, updateEmailMailPoetProperty ]
	);

	const finalLabel = (
		<>
			<span>{ label }</span>
			<Button
				className="mailpoet-settings-panel__personalization-tags-button"
				icon="shortcode"
				title={ __( 'Personalization Tags', 'mailpoet' ) }
				onClick={ () => setIsModalOpened( true ) }
			/>
			{ labelSuffix }
		</>
	);

	if ( ! mailpoetEmailData ) {
		return null;
	}

	return (
		<BaseControl
			id={ `mailpoet-settings-panel__${ attributeName }` }
			label={ finalLabel }
			className={ `mailpoet-settings-panel__${ attributeName }-text` }
			help={ help }
			__nextHasNoMarginBottom // To avoid warning about deprecation in console
		>
			<PersonalizationTagsModal
				isOpened={ isModalOpened }
				onInsert={ ( value ) => {
					handleInsertPersonalizationTag(
						value,
						mailpoetEmailData[ attributeName ] ?? '',
						selectionRange
					);
					setIsModalOpened( false );
				} }
				closeCallback={ () => setIsModalOpened( false ) }
			/>
			<PersonalizationTagsPopover
				contentRef={ richTextRef }
				onUpdate={ ( originalTag, updatedTag ) => {
					const currentValue =
						mailpoetEmailData[ attributeName ] ?? '';
					// When we update the tag, we need to add brackets to the tag, because the popover removes them
					const updatedContent = currentValue.replace(
						`<!--[${ originalTag }]-->`,
						`<!--[${ updatedTag }]-->`
					);
					updateEmailMailPoetProperty(
						attributeName,
						updatedContent
					);
				} }
			/>
			<RichText
				ref={ richTextRef }
				className="mailpoet-settings-panel__richtext"
				placeholder={ placeholder }
				onFocus={ () => {
					setSelectionRange(
						getCursorPosition(
							richTextRef,
							mailpoetEmailData[ attributeName ] ?? ''
						)
					);
				} }
				onKeyUp={ () => {
					setSelectionRange(
						getCursorPosition(
							richTextRef,
							mailpoetEmailData[ attributeName ] ?? ''
						)
					);
				} }
				onClick={ () => {
					setSelectionRange(
						getCursorPosition(
							richTextRef,
							mailpoetEmailData[ attributeName ] ?? ''
						)
					);
				} }
				onChange={ ( value ) => {
					value = replacePersonalizationTagsWithHTMLComments(
						value ?? '',
						list
					);
					updateEmailMailPoetProperty( attributeName, value );
				} }
				value={ mailpoetEmailData[ attributeName ] ?? '' }
				data-automation-id={ `email_${ attributeName }` }
			/>
		</BaseControl>
	);
}
