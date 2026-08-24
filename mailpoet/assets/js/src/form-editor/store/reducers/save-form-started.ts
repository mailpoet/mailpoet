import { dispatch } from '@wordpress/data';
import { State } from '../state-types';

// The literal rather than the storeName export from '../constants': that module
// pulls in @wordpress/block-editor, which has no window in the Node test env and
// would break every spec that touches this reducer.
const STORE_NAME = 'mailpoet-form-editor';

export const saveFormStartedFactory =
  (MailPoet) =>
  (state: State): State => {
    // remove all form saving related notices
    const notices = state.notices.filter(
      (notice) =>
        ![
          'missing-lists-in-custom-segments-block',
          'save-form',
          'missing-lists',
          'missing-block',
          'missing-tracking-consent',
        ].includes(notice.id),
    );
    const hasMissingLists =
      state.formErrors.includes('missing-lists') ||
      state.formErrors.includes('missing-lists-in-custom-segments-block');
    const sidebarOpenedPanels = [...state.sidebar.openedPanels];
    if (hasMissingLists) {
      notices.push({
        id: 'missing-lists',
        content: MailPoet.I18n.t('settingsPleaseSelectList'),
        isDismissible: true,
        status: 'error',
      });
      if (!sidebarOpenedPanels.includes('basic-settings')) {
        sidebarOpenedPanels.push('basic-settings');
      }
    }

    const hasMissingEmail = state.formErrors.includes('missing-email-input');
    const hasMissingSubmit = state.formErrors.includes('missing-submit');
    if (hasMissingEmail || hasMissingSubmit) {
      notices.push({
        id: 'missing-block',
        content: MailPoet.I18n.t('missingObligatoryBlock'),
        isDismissible: true,
        status: 'error',
      });
    }

    const hasMissingTrackingConsent = state.formErrors.includes(
      'missing-tracking-consent',
    );
    if (hasMissingTrackingConsent) {
      // Deliberately not dismissible: components/notices.jsx auto-removes
      // dismissible notices after five seconds, which is not long enough to
      // read an error and click its action. Non-dismissible notices are
      // cleared at the start of the next save, which is when this re-runs.
      notices.push({
        id: 'missing-tracking-consent',
        content: MailPoet.I18n.t('missingTrackingConsentBlock'),
        isDismissible: false,
        status: 'error',
        actions: [
          {
            label: MailPoet.I18n.t('addTrackingConsentBlock'),
            onClick: () =>
              void dispatch(STORE_NAME).insertTrackingConsentBlock(),
          },
        ],
      });
    }

    return {
      ...state,
      // Without excluding the new error here the Save button would sit in its
      // busy state forever: SAVE_FORM returns early when formErrors is
      // non-empty, and nothing else resets isFormSaving.
      isFormSaving: !hasMissingLists && !hasMissingTrackingConsent,
      sidebar: {
        ...state.sidebar,
        activeTab: hasMissingLists ? 'form' : state.sidebar.activeTab,
        openedPanels: sidebarOpenedPanels,
      },
      notices,
    };
  };
