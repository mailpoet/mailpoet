import { MailPoet } from 'mailpoet';
import _ from 'underscore';
import { CopyToClipboardButton } from 'common/button/copy-to-clipboard-button';

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
  const id = 'mailpoet-system-info';

  const systemInfoData = window.systemInfoData;
  return (
    <>
      <div className="mailpoet_notice notice inline">
        <p>{MailPoet.I18n.t('systemInfoIntro')}</p>
      </div>

      {printData(systemInfoData, id)}
      <CopyToClipboardButton variant="secondary" targetId={id} />
    </>
  );
}
