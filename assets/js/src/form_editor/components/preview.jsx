import React, {
  useEffect,
  useState,
  useCallback,
} from 'react';
import MailPoet from 'mailpoet';
import { Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import Preview from '../../common/preview/preview.jsx';
import Modal from '../../common/modal/modal.jsx';
import { blocksToFormBodyFactory } from '../store/blocks_to_form_body.jsx';

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
  const formData = useSelect(
    (select) => select('mailpoet-form-editor').getFormData(),
    []
  );
  const { hidePreview } = useDispatch('mailpoet-form-editor');
  const isPreview = useSelect(
    (select) => select('mailpoet-form-editor').getIsPreviewShown(),
    []
  );

  const editorSettings = useSelect(
    (select) => select('core/block-editor').getSettings(),
    []
  );

  const loadFormPreviewFromServer = useCallback(() => {
    const blocksToFormBody = blocksToFormBodyFactory(
      editorSettings.colors,
      editorSettings.fontSizes,
      customFields
    );
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'forms',
      action: 'previewEditor',
      data: {
        body: blocksToFormBody(formBlocks),
        settings: formData.settings,
      },
    }).done((response) => {
      setForm(response.data);
    });
  }, [formBlocks, customFields, formData, editorSettings.colors, editorSettings.fontSizes]);

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

  const urlData = {
    id: formData.id,
  };
  const iframeSrc = `${window.mailpoet_form_preview_page}&data=${btoa(JSON.stringify(urlData))}`;
  return (
    <Modal
      title={MailPoet.I18n.t('formPreview')}
      onRequestClose={onClose}
      fullScreen
      contentClassName="mailpoet_form_preview_modal"
    >
      {form === null && (
        <div className="mailpoet_spinner_wrapper">
          <Spinner />
        </div>
      )}
      {form !== null && (
        <Preview>
          <iframe
            className="mailpoet_form_preview_iframe"
            src={iframeSrc}
            title={MailPoet.I18n.t('formPreview')}
          />
        </Preview>
      )}
    </Modal>
  );
};

export default FormPreview;
