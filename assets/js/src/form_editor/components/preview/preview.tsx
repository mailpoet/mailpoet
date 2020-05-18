import React, {
  useEffect,
  useState,
  useRef,
} from 'react';
import MailPoet from 'mailpoet';
import { Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import Preview from 'common/preview/preview.jsx';
import Modal from 'common/modal/modal.jsx';
import { onChange } from 'common/functions';
import BelowPostsSettings from './below_posts_settings';
import PopUpSettings from './popup_settings';
import OtherSettings from './other_settings';
import FixedBarSettings from './fixed_bar_settings';
import SlideInSettings from './slide_in_settings';

const FormPreview = () => {
  const iframeElement = useRef(null);
  const [iframeLoaded, setIframeLoaded] = useState(false);
  const { hidePreview, changePreviewSettings } = useDispatch('mailpoet-form-editor');
  const isPreview = useSelect(
    (select) => select('mailpoet-form-editor').getIsPreviewShown(),
    []
  );
  const isPreviewReady = useSelect(
    (select) => select('mailpoet-form-editor').getIsPreviewReady(),
    []
  );

  const previewSettings = useSelect(
    (select) => select('mailpoet-form-editor').getPreviewSettings(),
    []
  );

  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    []
  );

  const formId = useSelect(
    (select) => select('mailpoet-form-editor').getFormData().id,
    []
  );

  useEffect(() => {
    setIframeLoaded(false);
  }, [isPreview]);

  useEffect(() => {
    if (!iframeElement.current) {
      return;
    }
    const data = { formType: previewSettings.formType, formSettings };
    iframeElement.current.contentWindow.postMessage(
      data,
      (window as any).mailpoet_form_preview_page
    );
  }, [formSettings, iframeElement, previewSettings]);

  if (!isPreview) return null;

  function setFormType(type) {
    setIframeLoaded(false);
    changePreviewSettings({ ...previewSettings, formType: type });
  }

  function onPreviewTypeChange(type) {
    changePreviewSettings({ ...previewSettings, displayType: type });
  }

  const urlData = {
    id: formId,
    form_type: previewSettings.formType,
  };
  let iframeSrc = `${(window as any).mailpoet_form_preview_page}&data=${btoa(JSON.stringify(urlData))}`;
  // Add anchor to scroll to certain types of form
  if (['below_post'].includes(previewSettings.formType)) {
    iframeSrc += `#mailpoet_form_preview_${formId}`;
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
        <div className="mailpoet_preview_content_wrapper">
          <div className="mailpoet_preview_sidebar">
            <label>
              {MailPoet.I18n.t('formPlacementLabel')}
              {' '}
              <select
                onChange={onChange(setFormType)}
                value={previewSettings.formType}
                data-automation-id="form_type_selection"
              >
                <option value="others">{MailPoet.I18n.t('placeFormOthers')}</option>
                <option value="below_post">{MailPoet.I18n.t('placeFormBellowPages')}</option>
                <option value="fixed_bar">{MailPoet.I18n.t('placeFixedBarFormOnPages')}</option>
                <option value="popup">{MailPoet.I18n.t('placePopupFormOnPages')}</option>
                <option value="slide_in">{MailPoet.I18n.t('placeSlideInFormOnPages')}</option>
              </select>
            </label>
            {previewSettings.formType === 'others' && <OtherSettings />}
            {previewSettings.formType === 'below_post' && <BelowPostsSettings />}
            {previewSettings.formType === 'fixed_bar' && <FixedBarSettings />}
            {previewSettings.formType === 'popup' && <PopUpSettings />}
            {previewSettings.formType === 'slide_in' && <SlideInSettings />}
          </div>
          <Preview
            onDisplayTypeChange={onPreviewTypeChange}
            selectedDisplayType={previewSettings.displayType}
          >
            {!iframeLoaded && (
              <div className="mailpoet_spinner_wrapper">
                <Spinner />
              </div>
            )}
            <iframe
              ref={iframeElement}
              className="mailpoet_form_preview_iframe"
              src={iframeSrc}
              title={MailPoet.I18n.t('formPreview')}
              onLoad={() => setIframeLoaded(true)}
              data-automation-id="form_preview_iframe"
              scrolling={previewSettings.formType === 'others' ? 'no' : 'yes'}
            />
            {previewSettings.formType === 'others' && previewSettings.displayType === 'desktop' && (
              <div className="mailpoet_form_preview_disclaimer">
                {MailPoet.I18n.t('formPreviewOthersDisclaimer')}
              </div>
            )}
          </Preview>
        </div>
      )}
    </Modal>
  );
};

export default FormPreview;
