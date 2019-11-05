import { select, dispatch } from '@wordpress/data';
import MailPoet from 'mailpoet';

export default {
  SAVE_FORM() {
    const formData = select('mailpoet-form-editor').getFormData();
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'forms',
      action: 'saveEditor',
      data: formData,
    }).done(() => {
      dispatch('mailpoet-form-editor').saveFormDone();
      dispatch('mailpoet-form-editor').addDismissibleNotice(MailPoet.I18n.t('formSaved'), 'success', 'save-form');
    }).fail((response) => {
      let errorMessage = null;
      if (response.errors.length > 0) {
        errorMessage = response.errors.map((error) => (error.message));
      }
      dispatch('mailpoet-form-editor').saveFormDone();
      dispatch('mailpoet-form-editor').addDismissibleNotice(errorMessage, 'error', 'save-form');
    });
  },
};
