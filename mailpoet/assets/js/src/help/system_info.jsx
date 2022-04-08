import MailPoet from 'mailpoet';
import _ from 'underscore';

function handleFocus(event) {
  event.target.select();
}

function printData(data) {
  if (_.isObject(data)) {
    const printableData = Object.keys(data).map(
      (key) => `${key}: ${data[key]}`,
    );

    return (
      <textarea
        readOnly
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

function SystemInfo() {
  const systemInfoData = window.systemInfoData;
  return (
    <>
      <div className="mailpoet_notice notice inline">
        <p>{MailPoet.I18n.t('systemInfoIntro')}</p>
      </div>

      {printData(systemInfoData)}
    </>
  );
}

export default SystemInfo;
