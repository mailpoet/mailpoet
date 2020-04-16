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
import mapFormDataBeforeSaving from '../store/map_form_data_before_saving.jsx';
import { onChange } from '../../common/functions';

function getFormType(settings) {
  const storedValue = window.localStorage.getItem('mailpoet_form_preview_last_form_type');
  if (storedValue) {
    return storedValue;
  }
  if (settings.placeFormBellowAllPages || settings.placeFormBellowAllPosts) {
    return 'below_post';
  }
  if (settings.placePopupFormOnAllPages || settings.placePopupFormOnAllPosts) {
    return 'popup';
  }
  if (settings.placeFixedBarFormOnAllPages || settings.placeFixedBarFormOnAllPosts) {
    return 'fixed_bar';
  }
  return 'sidebar';
}

const getPreviewType = () => (window.localStorage.getItem('mailpoet_form_preview_last_preview_type') || 'desktop');

const FormPreview = () => {
  const [formPersisted, setFormPersisted] = useState(false);
  const [iframeLoaded, setIframeLoaded] = useState(false);

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

  const saveFormDataForPreview = useCallback(() => {
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
        ...mapFormDataBeforeSaving(formData),
        body: blocksToFormBody(formBlocks),
      },
    }).done(() => {
      setFormPersisted(true);
    });
  }, [formBlocks, customFields, formData, editorSettings.colors, editorSettings.fontSizes]);

  useEffect(() => {
    if (isPreview) {
      saveFormDataForPreview();
    }
    setIframeLoaded(false);
  }, [isPreview, saveFormDataForPreview]);

  if (!isPreview) return null;

  function onClose() {
    setFormPersisted(false);
    hidePreview();
  }

  function setFormType(type) {
    setIframeLoaded(false);
    window.localStorage.setItem('mailpoet_form_preview_last_form_type', type);
  }

  function setPreviewType(type) {
    window.localStorage.setItem('mailpoet_form_preview_last_preview_type', type);
  }

  const formType = getFormType(formData.settings);
  const previewType = getPreviewType();
  const urlData = {
    id: formData.id,
    form_type: formType,
  };
  let iframeSrc = `${window.mailpoet_form_preview_page}&data=${btoa(JSON.stringify(urlData))}`;
  // Add anchor to scroll to certain types of form
  if (['below_post'].includes(formType)) {
    iframeSrc += `#mailpoet_form_preview_${formData.id}`;
  }
  return (
    <Modal
      onRequestClose={onClose}
      fullScreen
      contentClassName="mailpoet_form_preview_modal"
    >
      {!formPersisted && (
        <div className="mailpoet_spinner_wrapper">
          <Spinner />
        </div>
      )}
      {formPersisted && (
        <>
          <div className="mailpoet_form_preview_type_select">
            <label>
              {MailPoet.I18n.t('formPlacement')}
              {' '}
              <select
                onChange={onChange(setFormType)}
                value={formType}
                data-automation-id="form_type_selection"
              >
                <option value="sidebar">{MailPoet.I18n.t('placeFormSidebar')}</option>
                <option value="below_post">{MailPoet.I18n.t('placeFormBellowPages')}</option>
                <option value="fixed_bar">{MailPoet.I18n.t('placeFixedBarFormOnPages')}</option>
                <option value="popup">{MailPoet.I18n.t('placePopupFormOnPages')}</option>
                <option value="slide_in">{MailPoet.I18n.t('placeSlideInFormOnPages')}</option>
              </select>
            </label>
          </div>
          <Preview
            onChange={setPreviewType}
            selectedType={previewType}
          >
            {!iframeLoaded && (
              <div className="mailpoet_spinner_wrapper">
                <Spinner />
              </div>
            )}
            <iframe
              className="mailpoet_form_preview_iframe"
              src={iframeSrc}
              title={MailPoet.I18n.t('formPreview')}
              onLoad={() => setIframeLoaded(true)}
              data-automation-id="form_preview_iframe"
            />
          </Preview>
        </>
      )}
    </Modal>
  );
};

export default FormPreview;
