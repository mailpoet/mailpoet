import { dispatch, select } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';
import { __ } from '@wordpress/i18n';
import type { Action } from '@wordpress/dataviews';
import { createEmailPreset, SaveAsPresetForm } from 'common/email-presets';
import { MAILPOET_EMAIL_POST_TYPE } from '../constants';

type EmailPost = {
  id: number;
  type: string;
  mailpoet_data?: { subject?: string };
};

const saveAsPreset = async (post: EmailPost, title: string) => {
  try {
    // The preset copies the saved email, so save pending changes first.
    if (select(editorStore).isEditedPostDirty()) {
      await dispatch(coreDataStore).saveEditedEntityRecord(
        'postType',
        MAILPOET_EMAIL_POST_TYPE,
        post.id,
        { throwOnError: true },
      );
    }
    const preset = await createEmailPreset(title, post.id);
    void dispatch(noticesStore).createSuccessNotice(
      __('Email saved as a preset.', 'mailpoet'),
      {
        type: 'snackbar',
        actions: [
          { label: __('Edit preset', 'mailpoet'), url: preset.edit_url },
        ],
      },
    );
    return true;
  } catch (error) {
    void dispatch(noticesStore).createErrorNotice(
      (error as { message?: string })?.message ||
        __('The preset could not be saved. Please try again.', 'mailpoet'),
      { type: 'snackbar' },
    );
    return false;
  }
};

// Added to the Actions dropdown in the editor's Email sidebar.
export const saveAsPresetAction: Action<EmailPost> = {
  id: 'mailpoet-save-as-preset',
  label: __('Save as preset', 'mailpoet'),
  supportsBulk: false,
  isEligible: (post) => post.type === MAILPOET_EMAIL_POST_TYPE,
  modalHeader: __('Save as preset', 'mailpoet'),
  modalFocusOnMount: 'firstContentElement',
  RenderModal: ({ items, closeModal }) => (
    <SaveAsPresetForm
      defaultTitle={items[0].mailpoet_data?.subject ?? ''}
      onSubmit={(title) => saveAsPreset(items[0], title)}
      onClose={closeModal}
    />
  ),
};
