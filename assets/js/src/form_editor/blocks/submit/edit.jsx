import React from 'react';

import { InspectorControls } from '@wordpress/block-editor';
import { TextControl, Panel, PanelBody } from '@wordpress/components';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const SubmitEdit = ({ attributes, setAttributes }) => {
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            onChange={(label) => (setAttributes({ label }))}
          />
        </PanelBody>
      </Panel>

    </InspectorControls>
  );

  return (
    <>
      { inspectorControls }
      <div className="mailpoet_submit">
        <input className="button" type="submit" value={attributes.label} />
      </div>
    </>
  );
};

SubmitEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default SubmitEdit;
