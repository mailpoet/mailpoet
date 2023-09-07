import { useState } from 'react';
import { MailPoet } from 'mailpoet';
import _ from 'underscore';
import { Notice } from '../notices/notice';
import { Button } from '../common';
import { copyToClipboard } from '../utils';

function handleFocus(event) {
  event.target.select();
}

function printData(data: Record<string, string> | undefined, id: string) {
  if (_.isObject(data)) {
    const printableData = Object.keys(data).map(
      (key) => `${key}: ${data[key]}`,
    );

    return (
      <textarea
        readOnly
        id={id}
        onFocus={handleFocus}
        value={printableData.join('\n')}
        style={{
          width: '100%',
          height: '400px',
        }}
      />
    );
  }
  return <p>{MailPoet.I18n.t('systemInfoDataError')}</p>;
}

export function SystemInfo() {
  const [copySuccess, setCopySuccess] = useState(null);
  const id = 'mailpoet-system-info';

  const systemInfoData = window.systemInfoData;
  return (
    <>
      <div className="mailpoet_notice notice inline">
        <p>{MailPoet.I18n.t('systemInfoIntro')}</p>
      </div>

      {printData(systemInfoData, id)}
      <Button
        variant="secondary"
        onClick={() => {
          void copyToClipboard(id, setCopySuccess);
        }}
      >
        {MailPoet.I18n.t('copyToClipboard')}
      </Button>
      {copySuccess === true && (
        <Notice type="info">
          <p>{MailPoet.I18n.t('copyToClipboardSuccess')}</p>
        </Notice>
      )}
      {copySuccess === false && (
        <Notice type="warning">
          <p>{MailPoet.I18n.t('copyToClipboardFailure')}</p>
        </Notice>
      )}
    </>
  );
}
