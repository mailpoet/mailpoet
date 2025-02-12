import { ExternalLink } from '@wordpress/components';
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import classnames from 'classnames';

const previewTextMaxLength = 150;
const previewTextRecommendedLength = 80;

export function EmailSidebarExtensionBody({ RichTextWithButton }) {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );

  const updateEmailMailPoetProperty = (name: string, value: string) => {
    const postId = select(editorStore).getCurrentPostId();
    const currentPostType = 'mailpoet_email'; // only for mailpoet_email post-type

    const editedPost = select(coreDataStore).getEditedEntityRecord(
      'postType',
      currentPostType,
      postId,
    );

    // @ts-expect-error Property 'mailpoet_data' does not exist on type 'Updatable<Attachment<any>>'.
    const mailpoetData = editedPost?.mailpoet_data || {};
    void dispatch(coreDataStore).editEntityRecord(
      'postType',
      currentPostType,
      postId,
      {
        mailpoet_data: {
          ...mailpoetData,
          [name]: value,
        },
      },
    );
  };

  const subjectHelp = createInterpolateElement(
    __(
      'Use personalization tags to personalize your email, or learn more about <bestPracticeLink>best practices</bestPracticeLink> and using <emojiLink>emoji in subject lines</emojiLink>.',
      'mailpoet',
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
    },
  );

  const previewTextLength = mailpoetEmailData?.preheader?.length ?? 0;

  const preheaderHelp = createInterpolateElement(
    __(
      '<link>This text</link> will appear in the inbox, underneath the subject line.',
      'mailpoet',
    ),
    {
      link: (
        // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
        <a
          href={new URL(
            'article/418-preview-text',
            'https://kb.mailpoet.com/',
          ).toString()}
          key="preview-text-kb"
          target="_blank"
          rel="noopener noreferrer"
        />
      ),
    },
  );

  return (
    <>
      <RichTextWithButton
        attributeName="subject"
        attributeValue={mailpoetEmailData?.subject}
        updateProperty={updateEmailMailPoetProperty}
        label={__('Subject', 'mailpoet')}
        labelSuffix={
          <ExternalLink href="https://kb.mailpoet.com/article/435-a-guide-to-personalisation-tags-for-tailored-newsletters#list">
            {__('Guide', 'mailpoet')}
          </ExternalLink>
        }
        help={subjectHelp}
        placeholder={__('Eg. The summer sale is here!', 'mailpoet')}
      />

      <br />

      <RichTextWithButton
        attributeName="preheader"
        attributeValue={mailpoetEmailData?.preheader}
        updateProperty={updateEmailMailPoetProperty}
        label={__('Preview text', 'mailpoet')}
        labelSuffix={
          <span
            className={classnames(
              'mailpoet-settings-panel__preview-text-length',
              {
                'mailpoet-settings-panel__preview-text-length-warning':
                  previewTextLength > previewTextRecommendedLength,
                'mailpoet-settings-panel__preview-text-length-error':
                  previewTextLength > previewTextMaxLength,
              },
            )}
          >
            {previewTextLength}/{previewTextMaxLength}
          </span>
        }
        help={preheaderHelp}
        placeholder={__(
          "Add a preview text to capture subscribers' attention and increase open rates.",
          'mailpoet',
        )}
      />
    </>
  );
}

export function EmailSidebarExtension(RichTextWithButton: JSX.Element) {
  return <EmailSidebarExtensionBody RichTextWithButton={RichTextWithButton} />;
}
