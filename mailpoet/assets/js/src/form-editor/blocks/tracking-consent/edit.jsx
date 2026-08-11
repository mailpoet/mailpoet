import { Panel, PanelBody, TextControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';

import { ParagraphEdit } from '../paragraph-edit.jsx';

function TrackingConsentEdit({ attributes, setAttributes }) {
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            data-automation-id="settings_tracking_consent_label_input"
            onChange={(label) => setAttributes({ label })}
          />
          <TextControl
            label={MailPoet.I18n.t('blockTrackingConsentCheckboxLabel')}
            help={MailPoet.I18n.t('blockTrackingConsentCopyHelp')}
            value={attributes.consentText}
            data-automation-id="settings_tracking_consent_text_input"
            onChange={(consentText) => setAttributes({ consentText })}
          />
        </PanelBody>
      </Panel>
    </InspectorControls>
  );

  return (
    <ParagraphEdit className={attributes.className}>
      {inspectorControls}
      <span
        className="mailpoet_checkbox_label"
        data-automation-id="editor_tracking_consent_block"
      >
        {attributes.label}
      </span>
      <div>
        <label>
          {/* Consent can only ever be collected unticked (CJEU Planet49), so
              there is nothing here to configure. */}
          <input
            type="checkbox"
            disabled
            checked={false}
            readOnly
            className="mailpoet_checkbox"
          />
          <span>{attributes.consentText}</span>
        </label>
      </div>
    </ParagraphEdit>
  );
}

TrackingConsentEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    consentText: PropTypes.string.isRequired,
    className: PropTypes.string,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export { TrackingConsentEdit };
