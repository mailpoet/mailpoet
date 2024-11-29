import {
	BaseControl,
	Button,
	ExternalLink,
	PanelBody,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import classnames from 'classnames';
import { storeName } from '../../store';
import { RichText } from '@wordpress/block-editor';

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
				onClick={ () => togglePersonalizationTagsModal( true ) }
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
				onClick={ () => togglePersonalizationTagsModal( true ) }
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
					className="mailpoet-settings-panel__richtext"
					placeholder={ __(
						'Eg. The summer sale is here!',
						'mailpoet'
					) }
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
					className="mailpoet-settings-panel__richtext"
					placeholder={ __(
						"Add a preview text to capture subscribers' attention and increase open rates.",
						'mailpoet'
					) }
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
