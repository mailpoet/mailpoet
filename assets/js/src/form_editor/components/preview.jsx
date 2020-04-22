import React, {
  useEffect,
  useState,
} from 'react';
import MailPoet from 'mailpoet';
import { Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import Preview from '../../common/preview/preview.jsx';
import Modal from '../../common/modal/modal.jsx';
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
  const [iframeLoaded, setIframeLoaded] = useState(false);
  const [previewType, setPreviewType] = useState(getPreviewType());

  const { hidePreview } = useDispatch('mailpoet-form-editor');
  const isPreview = useSelect(
    (select) => select('mailpoet-form-editor').getIsPreviewShown(),
    []
  );
  const isPreviewReady = useSelect(
    (select) => select('mailpoet-form-editor').getIsPreviewReady(),
    []
  );

  const formData = useSelect(
    (select) => select('mailpoet-form-editor').getFormData(),
    []
  );

  useEffect(() => {
    setIframeLoaded(false);
  }, [isPreview]);

  if (!isPreview) return null;

  function setFormType(type) {
    setIframeLoaded(false);
    window.localStorage.setItem('mailpoet_form_preview_last_form_type', type);
  }

  function onPreviewTypeChange(type) {
    setPreviewType(type);
    window.localStorage.setItem('mailpoet_form_preview_last_preview_type', type);
  }

  const formType = getFormType(formData.settings);
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
      onRequestClose={hidePreview}
      fullScreen
      contentClassName="mailpoet_form_preview_modal"
      overlayClassName="mailpoet_form_preview_modal_overlay"
    >
      {!isPreviewReady && (
        <div className="mailpoet_spinner_wrapper">
          <Spinner />
        </div>
      )}
      {isPreviewReady && (
        <>
          <div className="mailpoet_form_preview_type_select">
            <label>
              {MailPoet.I18n.t('formPlacementLabel')}
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
            onDisplayTypeChange={onPreviewTypeChange}
            selectedDisplayType={previewType}
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
              scrolling={formType === 'sidebar' ? 'no' : 'yes'}
            />
            {formType === 'sidebar' && previewType === 'desktop' && (
              <div className="mailpoet_form_preview_disclaimer">
                {MailPoet.I18n.t('formPreviewSidebarDisclaimer')}
              </div>
            )}
          </Preview>
        </>
      )}
    </Modal>
  );
};

export default FormPreview;
