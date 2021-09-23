import React, {
  useCallback,
  useEffect,
  useState,
  useRef,
} from 'react';
import MailPoet from 'mailpoet';
import { Spinner, SelectControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import Preview from 'common/preview/preview.jsx';
import Modal from 'common/modal/modal';
import PlacementSettingsPanel from 'form_editor/components/form_settings/form_placement_options/settings_panel';

const FormPreview: React.FunctionComponent = () => {
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

  const editorUrl = useSelect(
    (select) => select('mailpoet-form-editor').getEditorUrl(),
    []
  );

  const previewPageUrl = useSelect(
    (select) => select('mailpoet-form-editor').getPreviewPageUrl(),
    []
  );

  useEffect(() => {
    setIframeLoaded(false);
    const beacon = document.getElementById('beacon-container');
    if (isPreview && beacon) { // hide beacon in the preview modal
      beacon.style.display = 'none';
    }
  }, [isPreview]);

  useEffect(() => {
    if (!iframeElement.current || !iframeLoaded) {
      return;
    }
    const data = { formType: previewSettings.formType, formSettings };
    iframeElement.current.contentWindow.postMessage(data, previewPageUrl);
  }, [formSettings, iframeElement, previewSettings, iframeLoaded, previewPageUrl]);

  const closePreview = useCallback((): void => {
    const beacon = document.getElementById('beacon-container');
    if (beacon) {
      beacon.style.display = 'block';
    }
    hidePreview();
  }, [hidePreview]);

  const setFormType = useCallback((type): void => {
    setIframeLoaded(false);
    changePreviewSettings({ ...previewSettings, formType: type });
  }, [changePreviewSettings, previewSettings]);

  const onPreviewTypeChange = useCallback((type): void => {
    changePreviewSettings({ ...previewSettings, displayType: type });
  }, [changePreviewSettings, previewSettings]);

  if (!isPreview) return null;

  const urlData = {
    id: formId,
    form_type: previewSettings.formType,
    editor_url: editorUrl,
  };
  let iframeSrc = `${previewPageUrl}&data=${btoa(JSON.stringify(urlData))}`;
  // Add anchor to scroll to certain types of form
  if (['below_post'].includes(previewSettings.formType)) {
    iframeSrc += `#mailpoet_form_preview_${formId}`;
  }
  return (
    <Modal
      onRequestClose={closePreview}
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
            <SelectControl
              label={MailPoet.I18n.t('formPlacementLabel')}
              value={previewSettings.formType}
              onChange={setFormType}
              className="mailpoet_preview_form_type_selection"
              data-automation-id="form_type_selection"
              options={[
                { value: 'others', label: MailPoet.I18n.t('placeFormOthers') },
                { value: 'below_post', label: MailPoet.I18n.t('placeFormBellowPages') },
                { value: 'fixed_bar', label: MailPoet.I18n.t('placeFixedBarFormOnPages') },
                { value: 'popup', label: MailPoet.I18n.t('placePopupFormOnPages') },
                { value: 'slide_in', label: MailPoet.I18n.t('placeSlideInFormOnPages') },
              ]}
            />
            <PlacementSettingsPanel activePanel={previewSettings.formType} />
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
              onLoad={(): void => setIframeLoaded(true)}
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
