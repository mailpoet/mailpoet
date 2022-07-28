import { FunctionComponent, useState } from 'react';
import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { useDispatch, useSelect } from '@wordpress/data';
import { curry } from 'lodash';
import { assocPath } from 'lodash/fp';
import { TextareaControl } from '@wordpress/components';
import { SizeSettings } from 'form_editor/components/size_settings';

export function OtherSettings(): JSX.Element {
  const [copyAreaContent, setCopyAreaContent] = useState(null);

  const formExports = useSelect(
    (select) => select('mailpoet-form-editor').getFormExports(),
    [],
  );

  const formSettings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );

  const isFormSaved = useSelect(
    (select) => select('mailpoet-form-editor').isFormSaved(),
    [],
  );
  const { changeFormSettings } = useDispatch('mailpoet-form-editor');

  const addFormWidgetHint = ReactStringReplace(
    MailPoet.I18n.t('addFormWidgetHint'),
    /\[link](.*?)\[\/link]/g,
    (match) => (
      <a key="addFormWidgetHintLink" href="widgets.php" target="_blank">
        {match}
      </a>
    ),
  );

  const addFormShortcodeHint = ReactStringReplace(
    MailPoet.I18n.t('addFormShortcodeHint'),
    /\[shortcode]/g,
    (match) => <code key={match}>{formExports.shortcode}</code>,
  );

  const exportLinkClicked = curry((type, event) => {
    event.preventDefault();
    MailPoet.trackEvent('Forms > Embed', {
      'Embed type': type,
    });
    if (type === 'php') {
      return setCopyAreaContent(formExports.php);
    }
    return setCopyAreaContent(formExports.iframe);
  });

  const addFormPhpIframeHint = ReactStringReplace(
    MailPoet.I18n.t('addFormPhpIframeHint'),
    /\[link](.*?)\[\/link]/g,
    (match) => {
      if (match === 'PHP') {
        return (
          <a key="exportPHP" href="#" onClick={exportLinkClicked('php')}>
            {match}
          </a>
        );
      }
      return (
        <a key="exportIframe" href="#" onClick={exportLinkClicked('iframe')}>
          {match}
        </a>
      );
    },
  );

  const getCopyTextArea: FunctionComponent = () => {
    if (!copyAreaContent) return null;
    return (
      <TextareaControl
        key="copyTextArea"
        readOnly
        onClick={(event): void =>
          (event.target as HTMLTextAreaElement).select()
        }
        rows={8}
        value={copyAreaContent}
        onChange={() => {}}
      />
    );
  };

  if (!isFormSaved) {
    return <p>{MailPoet.I18n.t('saveFormFirst')}</p>;
  }

  return (
    <>
      <p>{addFormWidgetHint}</p>
      <p>{addFormShortcodeHint}</p>
      <p>{addFormPhpIframeHint}</p>
      {getCopyTextArea({})}
      <hr />
      <SizeSettings
        label={MailPoet.I18n.t('formSettingsWidth')}
        value={formSettings.formPlacement.others.styles.width}
        minPixels={200}
        maxPixels={1200}
        minPercents={10}
        maxPercents={100}
        defaultPixelValue={200}
        defaultPercentValue={100}
        onChange={(width): void => {
          void changeFormSettings(
            assocPath('formPlacement.others.styles.width', width, formSettings),
          );
        }}
      />
    </>
  );
}
