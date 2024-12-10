import {
	BaseControl,
	Button,
	ExternalLink,
	PanelBody,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useState, useRef } from '@wordpress/element';
import classnames from 'classnames';
import { storeName } from '../../store';
import { RichText } from '@wordpress/block-editor';
import {
	createTextToHtmlMap,
	getCursorPosition,
	isMatchingComment,
} from '../personalization-tags/rich-text-utils';

const previewTextMaxLength = 150;
const previewTextRecommendedLength = 80;

function PersonalizationTagsButton( { onClick } ) {
	return (
		<Button
			className="mailpoet-settings-panel__personalization-tags-button"
			icon="shortcode"
			title={ __( 'Personalization Tags', 'mailpoet' ) }
			onClick={ () => onClick() }
		/>
	);
}

export function DetailsPanel() {
	const [ mailpoetEmailData ] = useEntityProp(
		'postType',
		'mailpoet_email',
		'mailpoet_data'
	);

	const { togglePersonalizationTagsModal, updateEmailMailPoetProperty } =
		useDispatch( storeName );
	const [ activeRichText, setActiveRichText ] = useState( null );
	const [ selectionRange, setSelectionRange ] = useState( null );

	const subjectRef = useRef( null );
	const preheaderRef = useRef( null );

	const handleInsertPersonalizationTag = async ( value ) => {
		if ( ! activeRichText || ! selectionRange ) {
			return;
		}

		const ref = activeRichText === 'subject' ? subjectRef : preheaderRef;
		if ( ! ref ) {
			return;
		}

		// Retrieve the current value of the active RichText
		const currentValue =
			activeRichText === 'subject'
				? mailpoetEmailData?.subject ?? ''
				: mailpoetEmailData?.preheader ?? '';

		// Generate text-to-HTML mapping
		const { mapping } = createTextToHtmlMap( currentValue );

		// Ensure selection range is within bounds
		const start = selectionRange.start;
		const end = selectionRange.end;

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
			`<!--${ value }-->` +
			currentValue.slice( htmlEnd );

		// Update the corresponding property
		if ( activeRichText === 'subject' ) {
			updateEmailMailPoetProperty( 'subject', updatedValue );
		} else if ( activeRichText === 'preheader' ) {
			updateEmailMailPoetProperty( 'preheader', updatedValue );
		}

		setSelectionRange( null );
	};

	const subjectHelp = createInterpolateElement(
		__(
			'Use shortcodes to personalize your email, or learn more about <bestPracticeLink>best practices</bestPracticeLink> and using <emojiLink>emoji in subject lines</emojiLink>.',
			'mailpoet'
		),
		{
			bestPracticeLink: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
				<a
					href="https://www.mailpoet.com/blog/17-email-subject-line-best-practices-to-boost-engagement/"
					target="_blank"
					rel="noopener noreferrer"
				/>
			),
			emojiLink: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
				<a
					href="https://www.mailpoet.com/blog/tips-using-emojis-in-subject-lines/"
					target="_blank"
					rel="noopener noreferrer"
				/>
			),
		}
	);

	const subjectLabel = (
		<>
			<span>{ __( 'Subject', 'mailpoet' ) }</span>
			<PersonalizationTagsButton
				onClick={ () => {
					setActiveRichText( 'subject' );
					togglePersonalizationTagsModal( true, {
						onInsert: handleInsertPersonalizationTag,
					} );
				} }
			/>
			<ExternalLink href="https://kb.mailpoet.com/article/215-personalize-newsletter-with-shortcodes#list">
				{ __( 'Shortcode guide', 'mailpoet' ) }
			</ExternalLink>
		</>
	);

	const previewTextLength = mailpoetEmailData?.preheader?.length ?? 0;

	const preheaderHelp = createInterpolateElement(
		__(
			'<link>This text</link> will appear in the inbox, underneath the subject line.',
			'mailpoet'
		),
		{
			link: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
				<a
					href={ new URL(
						'article/418-preview-text',
						'https://kb.mailpoet.com/'
					).toString() }
					key="preview-text-kb"
					target="_blank"
					rel="noopener noreferrer"
				/>
			),
		}
	);
	const preheaderLabel = (
		<>
			<span>{ __( 'Preview text', 'mailpoet' ) }</span>
			<PersonalizationTagsButton
				onClick={ () => {
					setActiveRichText( 'preheader' );
					togglePersonalizationTagsModal( true, {
						onInsert: handleInsertPersonalizationTag,
					} );
				} }
			/>
			<span
				className={ classnames(
					'mailpoet-settings-panel__preview-text-length',
					{
						'mailpoet-settings-panel__preview-text-length-warning':
							previewTextLength > previewTextRecommendedLength,
						'mailpoet-settings-panel__preview-text-length-error':
							previewTextLength > previewTextMaxLength,
					}
				) }
			>
				{ previewTextLength }/{ previewTextMaxLength }
			</span>
		</>
	);

	return (
		<PanelBody
			title={ __( 'Details', 'mailpoet' ) }
			className="mailpoet-email-editor__settings-panel"
		>
			<BaseControl
				id="mailpoet-settings-panel__subject"
				label={ subjectLabel }
				className="mailpoet-settings-panel__subject"
				help={ subjectHelp }
			>
				<RichText
					ref={ subjectRef }
					className="mailpoet-settings-panel__richtext"
					placeholder={ __(
						'Eg. The summer sale is here!',
						'mailpoet'
					) }
					onFocus={ () => {
						setActiveRichText( 'subject' );
						setSelectionRange(
							getCursorPosition(
								subjectRef,
								mailpoetEmailData?.subject ?? ''
							)
						);
					} }
					onKeyUp={ () => {
						setActiveRichText( 'subject' );
						setSelectionRange(
							getCursorPosition(
								subjectRef,
								mailpoetEmailData?.subject ?? ''
							)
						);
					} }
					onClick={ () => {
						setActiveRichText( 'subject' );
						setSelectionRange(
							getCursorPosition(
								subjectRef,
								mailpoetEmailData?.subject ?? ''
							)
						);
					} }
					onChange={ ( value ) =>
						updateEmailMailPoetProperty( 'subject', value )
					}
					value={ mailpoetEmailData?.subject ?? '' }
					data-automation-id="email_subject"
				/>
			</BaseControl>

			<BaseControl
				id="mailpoet-settings-panel__richtext"
				label={ preheaderLabel }
				className="mailpoet-settings-panel__preview-text"
				help={ preheaderHelp }
			>
				<RichText
					ref={ preheaderRef }
					className="mailpoet-settings-panel__richtext"
					placeholder={ __(
						"Add a preview text to capture subscribers' attention and increase open rates.",
						'mailpoet'
					) }
					onFocus={ () => {
						setActiveRichText( 'preheader' );
						setSelectionRange(
							getCursorPosition(
								preheaderRef,
								mailpoetEmailData?.preheader ?? ''
							)
						);
					} }
					onKeyUp={ () => {
						setActiveRichText( 'preheader' );
						setSelectionRange(
							getCursorPosition(
								preheaderRef,
								mailpoetEmailData?.preheader ?? ''
							)
						);
					} }
					onClick={ () => {
						setActiveRichText( 'preheader' );
						setSelectionRange(
							getCursorPosition(
								preheaderRef,
								mailpoetEmailData?.preheader ?? ''
							)
						);
					} }
					onChange={ ( value ) =>
						updateEmailMailPoetProperty( 'preheader', value )
					}
					value={ mailpoetEmailData?.preheader ?? '' }
					data-automation-id="email_preview_text"
				/>
			</BaseControl>
		</PanelBody>
	);
}
