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
import { useSelect } from '@wordpress/data';
import { mapColorSlugToValue } from '../../store/blocks_to_form_body.jsx';

import ParagraphEdit from '../paragraph_edit.jsx';

const CustomHtmlEdit = ({ attributes, setAttributes, clientId }) => {
  const { fontColor, fontSize } = useSelect(
    (select) => {
      const settings = select('mailpoet-form-editor').getFormSettings();
      const { getSettings } = select('core/block-editor');
      const colorDefinitions = getSettings().colors;
      const parentBackgroundColor = mapColorSlugToValue(
        colorDefinitions,
        select('mailpoet-form-editor').getClosestParentAttribute(clientId, 'backgroundColor'),
        select('mailpoet-form-editor').getClosestParentAttribute(clientId, 'customBackgroundColor')
      );
      const parentTextColor = mapColorSlugToValue(
        colorDefinitions,
        select('mailpoet-form-editor').getClosestParentAttribute(clientId, 'textColor'),
        select('mailpoet-form-editor').getClosestParentAttribute(clientId, 'customTextColor')
      );
      return {
        backgroundColor: parentBackgroundColor || settings.backgroundColor,
        fontColor: parentTextColor || settings.fontColor,
        fontSize: settings.fontSize,
      };
    },
    []
  );
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
  if (fontColor) styles.push(` body {color: ${fontColor};}`);
  if (fontSize) styles.push(` body {font-size: ${fontSize}px }`);
  const key = `${renderedContent}_${styles}`;
  return (
    <ParagraphEdit className={attributes.className}>
      {inspectorControls}
      <div className="mailpoet-html-block-editor-content-wrapper">
        <SandBox html={renderedContent} styles={styles} key={key} />
      </div>
    </ParagraphEdit>
  );
};

CustomHtmlEdit.propTypes = {
  attributes: PropTypes.shape({
    content: PropTypes.string.isRequired,
    nl2br: PropTypes.bool.isRequired,
    className: PropTypes.string,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
  clientId: PropTypes.string.isRequired,
};

export default CustomHtmlEdit;
