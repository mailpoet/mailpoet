import {
  PanelRow,
  ToggleControl,
  __experimentalVStack as VStack,
} from '@wordpress/components';
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { isNewsletterShownInArchiveFromEditorValue } from 'common/newsletter-archive-visibility';

export function ArchiveVisibilityRow() {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );
  const currentShowInArchive = mailpoetEmailData?.show_in_archive as
    | boolean
    | undefined;

  const updateShowInArchive = (showInArchive: boolean) => {
    const postId = select(editorStore).getCurrentPostId();
    const currentPostType = 'mailpoet_email';

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
          show_in_archive: showInArchive,
        },
      },
    );
  };

  return (
    <PanelRow>
      <VStack spacing={2}>
        <ToggleControl
          checked={isNewsletterShownInArchiveFromEditorValue(
            currentShowInArchive,
          )}
          label={__('Show this newsletter in the archive', 'mailpoet')}
          help={__(
            'Shown on pages using the MailPoet archive shortcode.',
            'mailpoet',
          )}
          onChange={updateShowInArchive}
        />
      </VStack>
    </PanelRow>
  );
}
