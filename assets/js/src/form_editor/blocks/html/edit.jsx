import React, { useState, useCallback } from 'react';
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
import { debounce } from 'lodash';

import ParagraphEdit from '../paragraph_edit.jsx';

const CustomHtmlEdit = ({ attributes, setAttributes }) => {
  const [renderedContent, setRenderedContent] = useState(attributes.content);
  const setRenderedContentDebounced = useCallback(debounce((content) => {
    setRenderedContent(content);
  }, 300), []);

  const handleContentChange = (content) => {
    setAttributes({ content });
    setRenderedContentDebounced(content);
  };

  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextareaControl
            label={MailPoet.I18n.t('blockCustomHtmlContentLabel')}
            value={attributes.content}
            data-automation-id="settings_custom_html_content"
            rows={4}
            onChange={handleContentChange}
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
  const key = `${renderedContent}_${styles}`;
  return (
    <ParagraphEdit>
      {inspectorControls}
      <div>
        <SandBox html={renderedContent} styles={styles} key={key} />
      </div>
    </ParagraphEdit>
  );
};

CustomHtmlEdit.propTypes = {
  attributes: PropTypes.shape({
    content: PropTypes.string.isRequired,
    nl2br: PropTypes.bool.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default CustomHtmlEdit;
