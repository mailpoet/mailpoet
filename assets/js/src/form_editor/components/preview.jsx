import React, {
  useEffect,
  useState,
  useCallback,
  useRef,
  useLayoutEffect,
} from 'react';
import MailPoet from 'mailpoet';
import { Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import Preview from '../../common/preview.jsx';
import Modal from '../../common/modal/modal.jsx';
import { blocksToFormBodyFactory } from '../store/blocks_to_form_body.jsx';

const FormPreview = () => {
  const formEl = useRef(null);
  const [form, setForm] = useState(null);

  const formBlocks = useSelect(
    (select) => select('mailpoet-form-editor').getFormBlocks(),
    []
  );
  const customFields = useSelect(
    (select) => select('mailpoet-form-editor').getAllAvailableCustomFields(),
    []
  );
  const settings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
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
        settings,
      },
    }).done((response) => {
      setForm(response.data);
    });
  }, [formBlocks, customFields, settings, editorSettings.colors, editorSettings.fontSizes]);

  useEffect(() => {
    if (isPreview) {
      loadFormPreviewFromServer();
    }
  }, [isPreview, loadFormPreviewFromServer]);

  useLayoutEffect(() => {
    // eslint-disable-next-line camelcase
    if (formEl.current && form?.form_element_styles) {
      formEl.current.setAttribute('style', form.form_element_styles);
    }
  }, [formEl, form]);

  if (!isPreview) return null;

  function onClose() {
    setForm(null);
    hidePreview();
  }

  return (
    <Modal
      title={MailPoet.I18n.t('formPreview')}
      onRequestClose={onClose}
      fullScreen
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
            <form
              target="_self"
              method="post"
              className="mailpoet_form "
              noValidate
              ref={formEl}
            >
              {/* eslint-disable-next-line react/no-danger */}
              <div dangerouslySetInnerHTML={{ __html: form.html }} />
              <div className="mailpoet_message">
                <p className="mailpoet_validate_success">{MailPoet.I18n.t('successMessage')}</p>
                <p className="mailpoet_validate_error">{MailPoet.I18n.t('errorMessage')}</p>
              </div>
            </form>
          </div>
        </Preview>
      )}
    </Modal>
  );
};

export default FormPreview;
