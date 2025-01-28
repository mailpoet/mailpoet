/**
 * External dependencies
 */
import { ExternalLink, PanelBody } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { editorCurrentPostType } from '../../store';
import { recordEvent } from '../../events';
import { RichTextWithButton } from '../personalization-tags/rich-text-with-button';

const previewTextMaxLength = 150;
const previewTextRecommendedLength = 80;

export function DetailsPanel() {
	const [ mailpoetEmailData ] = useEntityProp(
		'postType',
		editorCurrentPostType,
		'mailpoet_data'
	);

	const subjectHelp = createInterpolateElement(
		__(
			'Use personalization tags to personalize your email, or learn more about <bestPracticeLink>best practices</bestPracticeLink> and using <emojiLink>emoji in subject lines</emojiLink>.',
			'mailpoet'
		),
		{
			bestPracticeLink: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
				<a
					href="https://www.mailpoet.com/blog/17-email-subject-line-best-practices-to-boost-engagement/"
					target="_blank"
					rel="noopener noreferrer"
					onClick={ () =>
						recordEvent(
							'details_panel_subject_help_best_practice_link_clicked'
						)
					}
				/>
			),
			emojiLink: (
				// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
				<a
					href="https://www.mailpoet.com/blog/tips-using-emojis-in-subject-lines/"
					target="_blank"
					rel="noopener noreferrer"
					onClick={ () =>
						recordEvent(
							'details_panel_subject_help_emoji_in_subject_lines_link_clicked'
						)
					}
				/>
			),
		}
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
					onClick={ () =>
						recordEvent(
							'details_panel_preheader_help_text_link_clicked'
						)
					}
				/>
			),
		}
	);

	return (
		<PanelBody
			title={ __( 'Details', 'mailpoet' ) }
			className="mailpoet-email-editor__settings-panel"
			onToggle={ ( data ) =>
				recordEvent( 'details_panel_body_toggle', { opened: data } )
			}
		>
			<RichTextWithButton
				attributeName="subject"
				label={ __( 'Subject', 'mailpoet' ) }
				labelSuffix={
					<ExternalLink
						href="https://kb.mailpoet.com/article/435-a-guide-to-personalisation-tags-for-tailored-newsletters#list"
						onClick={ () =>
							recordEvent(
								'details_panel_personalisation_tags_guide_link_clicked'
							)
						}
					>
						{ __( 'Guide', 'mailpoet' ) }
					</ExternalLink>
				}
				help={ subjectHelp }
				placeholder={ __( 'Eg. The summer sale is here!', 'mailpoet' ) }
			/>

			<RichTextWithButton
				attributeName="preheader"
				label={ __( 'Preview text', 'mailpoet' ) }
				labelSuffix={
					<span
						className={ classnames(
							'mailpoet-settings-panel__preview-text-length',
							{
								'mailpoet-settings-panel__preview-text-length-warning':
									previewTextLength >
									previewTextRecommendedLength,
								'mailpoet-settings-panel__preview-text-length-error':
									previewTextLength > previewTextMaxLength,
							}
						) }
					>
						{ previewTextLength }/{ previewTextMaxLength }
					</span>
				}
				help={ preheaderHelp }
				placeholder={ __(
					"Add a preview text to capture subscribers' attention and increase open rates.",
					'mailpoet'
				) }
			/>
		</PanelBody>
	);
}
