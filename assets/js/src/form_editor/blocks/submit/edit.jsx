import React from 'react';
import {
  Panel,
  PanelBody,
  TextControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import classnames from 'classnames';

import ParagraphEdit from '../paragraph_edit.jsx';
import StylesSettings from './styles_settings';

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
      <StylesSettings onChange={(styles) => setAttributes({ styles })} styles={attributes.styles} />
    </InspectorControls>
  );

  const styles = !attributes.styles.inheritFromTheme ? {
    fontWeight: attributes.styles.bold ? 'bold' : 'inherit',
    borderRadius: attributes.styles.borderRadius !== undefined ? `${attributes.styles.borderRadius}px` : 1,
    borderWidth: attributes.styles.borderSize !== undefined ? `${attributes.styles.borderSize}px` : '1px',
    borderColor: attributes.styles.borderColor || 'initial',
    borderStyle: 'solid',
    fontSize: attributes.styles.fontSize ? `${attributes.styles.fontSize}px` : 'inherit',
    color: attributes.styles.fontColor || 'inherit',
  } : {};

  if (attributes.styles.fullWidth) {
    styles.width = '100%';
  }

  if (attributes.styles.backgroundColor && !attributes.styles.inheritFromTheme) {
    styles.backgroundColor = attributes.styles.backgroundColor;
  }

  if (attributes.styles.backgroundColor && !attributes.styles.inheritFromTheme) {
    styles.backgroundColor = attributes.styles.backgroundColor;
  }

  const className = classnames('mailpoet_submit', { button: attributes.styles.inheritFromTheme });

  return (
    <ParagraphEdit className={attributes.className}>
      { inspectorControls }
      <input
        className={className}
        type="submit"
        value={attributes.label}
        data-automation-id="editor_submit_input"
        style={styles}
      />
    </ParagraphEdit>
  );
};

SubmitEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    className: PropTypes.string,
    styles: PropTypes.shape({
      fullWidth: PropTypes.bool.isRequired,
      inheritFromTheme: PropTypes.bool.isRequired,
      bold: PropTypes.bool,
      backgroundColor: PropTypes.string,
      borderSize: PropTypes.number,
      borderRadius: PropTypes.number,
      borderColor: PropTypes.string,
      fontColor: PropTypes.string,
      fontSize: PropTypes.number,
    }),
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default SubmitEdit;
