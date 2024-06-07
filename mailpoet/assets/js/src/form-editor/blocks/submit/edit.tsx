import classnames from 'classnames';
import { CSSProperties } from 'react';
import { Panel, PanelBody, TextControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { ParagraphEdit } from '../paragraph-edit.jsx';
import { StylesSettings } from './styles-settings';
import { InputBlockStyles } from '../../store/form-data-types';
import { storeName } from '../../store/constants';

type Props = {
  attributes: {
    label: string;
    styles: InputBlockStyles;
    className: string | null;
  };
  setAttributes: (attribute) => void;
};

function SubmitEdit({ attributes, setAttributes }: Props): JSX.Element {
  const settings = useSelect(
    (select) => select(storeName).getFormSettings(),
    [],
  );

  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            onChange={(label): void => setAttributes({ label })}
            data-automation-id="settings_submit_label_input"
          />
        </PanelBody>
      </Panel>
      <StylesSettings
        onChange={(styles): void => setAttributes({ styles })}
        styles={attributes.styles}
        formInputPadding={settings.inputPadding}
        formFontFamily={settings.fontFamily}
      />
    </InspectorControls>
  );

  const fontSize =
    typeof attributes.styles.fontSize === 'number'
      ? `${attributes.styles.fontSize}px`
      : attributes.styles.fontSize;

  const styles: CSSProperties = !attributes.styles.inheritFromTheme
    ? {
        fontWeight: attributes.styles.bold ? 'bold' : 'inherit',
        borderRadius:
          attributes.styles.borderRadius !== undefined
            ? `${attributes.styles.borderRadius}px`
            : 0,
        borderWidth:
          attributes.styles.borderSize !== undefined
            ? `${attributes.styles.borderSize}px`
            : '1px',
        borderColor: attributes.styles.borderColor || 'transparent',
        borderStyle: 'solid',
        fontSize: fontSize || 'inherit',
        color: attributes.styles.fontColor || 'inherit',
      }
    : {};

  if (attributes.styles.fullWidth) {
    styles.width = '100%';
  }

  if (
    attributes.styles.padding !== undefined &&
    !attributes.styles.inheritFromTheme
  ) {
    styles.padding = attributes.styles.padding;
  } else if (settings.inputPadding !== undefined) {
    styles.padding = settings.inputPadding;
  }

  if (
    attributes.styles.backgroundColor &&
    !attributes.styles.inheritFromTheme
  ) {
    styles.backgroundColor = attributes.styles.backgroundColor;
  }

  if (
    attributes.styles.backgroundColor &&
    !attributes.styles.inheritFromTheme
  ) {
    styles.backgroundColor = attributes.styles.backgroundColor;
  }

  if (attributes.styles.gradient && !attributes.styles.inheritFromTheme) {
    styles.backgroundColor = undefined;
    styles.background = attributes.styles.gradient;
  }

  if (attributes.styles.fontFamily && !attributes.styles.inheritFromTheme) {
    styles.fontFamily = attributes.styles.fontFamily;
  }

  const className = classnames('mailpoet_submit', {
    button: attributes.styles.inheritFromTheme,
  });

  return (
    <ParagraphEdit className={attributes.className}>
      {inspectorControls}
      <input
        className={className}
        type="submit"
        value={attributes.label}
        data-automation-id="editor_submit_input"
        style={styles}
      />
    </ParagraphEdit>
  );
}

export { SubmitEdit };
