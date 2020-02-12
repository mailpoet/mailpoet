import React from 'react';
import {
  Panel,
  PanelBody,
  TextControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

import ParagraphEdit from '../paragraph_edit.jsx';

const SubmitEdit = ({ attributes, setAttributes }) => {
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            onChange={(label) => (setAttributes({ label }))}
            data-automation-id="settings_submit_label_input"
          />
        </PanelBody>
      </Panel>

    </InspectorControls>
  );

  return (
    <ParagraphEdit>
      { inspectorControls }
      <div className="mailpoet_submit">
        <input
          className="button"
          type="submit"
          value={attributes.label}
          data-automation-id="editor_submit_input"
        />
      </div>
    </ParagraphEdit>
  );
};

SubmitEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default SubmitEdit;
