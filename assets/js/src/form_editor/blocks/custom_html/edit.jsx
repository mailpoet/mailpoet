import React from 'react';
import {
  Panel,
  PanelBody,
  TextareaControl,
  ToggleControl,
  SandBox,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const CustomHtmlEdit = ({ attributes, setAttributes }) => {
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextareaControl
            label={MailPoet.I18n.t('blockCustomHtmlContentLabel')}
            value={attributes.content}
            data-automation-id="settings_custom_html_content"
            rows={4}
            onChange={(content) => setAttributes({ content })}
          />
          <ToggleControl
            label={MailPoet.I18n.t('blockCustomHtmlNl2br')}
            checked={attributes.nl2br}
            onChange={(nl2br) => (setAttributes({ nl2br }))}
          />
        </PanelBody>
      </Panel>

    </InspectorControls>
  );
  const styles = attributes.nl2br ? ['body { white-space: pre-line; }'] : [];
  const key = `${attributes.content}_${styles}`;
  return (
    <>
      {inspectorControls}
      <div>
        <SandBox html={attributes.content} styles={styles} key={key} />
      </div>
    </>
  );
};

CustomHtmlEdit.propTypes = {
  attributes: PropTypes.shape({
    content: PropTypes.string.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default CustomHtmlEdit;
