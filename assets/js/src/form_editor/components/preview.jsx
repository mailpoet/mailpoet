import React, { useEffect, useState, useCallback } from 'react';
import MailPoet from 'mailpoet';
import { Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import Preview from '../../common/preview.jsx';
import Modal from '../../common/modal/modal.jsx';
import blocksToFormBody from '../store/blocks_to_form_body.jsx';

const FormPreview = () => {
  const [form, setForm] = useState(null);

  const formBlocks = useSelect(
    (select) => select('mailpoet-form-editor').getFormBlocks(),
    []
  );
  const customFields = useSelect(
    (select) => select('mailpoet-form-editor').getAllAvailableCustomFields(),
    []
  );

  const { hidePreview } = useDispatch('mailpoet-form-editor');
  const isPreview = useSelect(
    (select) => select('mailpoet-form-editor').getIsPreviewShown(),
    []
  );

  const loadFormPreviewFromServer = useCallback(() => {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'forms',
      action: 'previewEditor',
      data: {
        body: blocksToFormBody(formBlocks, customFields),
      },
    }).done((response) => {
      setForm(response.data);
    });
  }, [formBlocks, customFields]);

  useEffect(() => {
    if (isPreview) {
      loadFormPreviewFromServer();
    }
  }, [isPreview, loadFormPreviewFromServer]);

  if (!isPreview) return null;

  function onClose() {
    setForm(null);
    hidePreview();
  }

  return (
    <Modal
      title={MailPoet.I18n.t('formPreview')}
      onRequestClose={onClose}
    >
      {form === null && (
        <div className="mailpoet_spinner_wrapper">
          <Spinner />
        </div>
      )}
      {form !== null && (
        <Preview>
          <div>
            <style type="text/css">
              {'.mailpoet_hp_email_label { display: none }' }
              {form.css}
            </style>
            <div className="mailpoet_message">
              <p className="mailpoet_validate_success">{MailPoet.I18n.t('successMessage')}</p>
              <p className="mailpoet_validate_error">{MailPoet.I18n.t('errorMessage')}</p>
            </div>
            <div dangerouslySetInnerHTML={{ __html: form.html }} />
          </div>
        </Preview>
      )}
    </Modal>
  );
};

export default FormPreview;
