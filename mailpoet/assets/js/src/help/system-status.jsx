import { MailPoet } from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { useMemo, useState } from 'react';
import { copyToClipboard } from 'utils';
import { CronStatus } from './cron-status.jsx';
import { QueueStatus } from './queue-status';
import { ActionSchedulerStatus } from './action-scheduler-status';
import { DataInconsistencies } from './data-inconsistencies';

function printValue(value) {
  if (value === null || value === undefined || value === '') {
    return MailPoet.I18n.t('none');
  }
  if (typeof value === 'boolean') {
    return value ? MailPoet.I18n.t('yes') : MailPoet.I18n.t('no');
  }
  if (Array.isArray(value)) {
    if (value.length === 0) return MailPoet.I18n.t('none');
    return value
      .map((item) => {
        if (item && item.worker && item.message) {
          return `${item.worker}: ${item.message}`;
        }
        return `${item}`;
      })
      .join('; ');
  }
  if (typeof value === 'object') {
    return JSON.stringify(value);
  }
  return `${value}`;
}

function formatTimestamp(seconds) {
  if (!seconds) return MailPoet.I18n.t('unknown');
  return MailPoet.Date.full(seconds * 1000);
}

function addSection(lines, title) {
  lines.push('');
  lines.push(`### ${title} ###`);
}

function addBullet(lines, key, value) {
  lines.push(`  - ${key}: ${printValue(value)}`);
}

function addActivePluginsList(lines, plugins) {
  const items = (plugins || []).filter(Boolean);
  if (!items.length) {
    lines.push(`  - ${MailPoet.I18n.t('none')}`);
    return;
  }
  items.forEach((plugin) => {
    const latestVersion = plugin.versionLatest
      ? ` (update to version ${plugin.versionLatest} is available)`
      : '';
    lines.push(
      `  - ${plugin.name}: by ${plugin.author} - ${plugin.version}${latestVersion}`,
    );
  });
}

function formatOptional(value) {
  if (value === null || value === undefined || value === '') {
    return MailPoet.I18n.t('none');
  }
  return value;
}

function formatYesNo(value) {
  if (typeof value === 'boolean') {
    return value ? MailPoet.I18n.t('yes') : MailPoet.I18n.t('no');
  }
  const normalized = `${value || ''}`.trim().toLowerCase();
  if (normalized === 'yes' || normalized === 'no') {
    return MailPoet.I18n.t(normalized);
  }
  return printValue(value);
}

function parseCompositeField(value, knownKeys) {
  const result = {};
  if (!value || typeof value !== 'string') {
    return result;
  }

  if (knownKeys && knownKeys.length > 0) {
    // Backend encodes composite fields as "key: value - key: value - ...".
    // Values may themselves contain " - " (e.g. error messages), so we anchor
    // on the known key list rather than splitting. Keys are sorted longest-first
    // so that longer keys take precedence when one is a prefix of another.
    const sortedKeys = [...knownKeys].sort((a, b) => b.length - a.length);
    // Escape regex-special characters in each key name.
    const escapedKeys = sortedKeys.map((k) =>
      k.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'),
    );
    const regex = new RegExp(
      `(?:^| - )(?<key>${escapedKeys.join('|')}): `,
      'g',
    );
    const matches = [...value.matchAll(regex)];
    matches.forEach((match, i) => {
      const valueStart = match.index + match[0].length;
      const valueEnd = matches[i + 1] ? matches[i + 1].index : value.length;
      result[match.groups.key] = value.slice(valueStart, valueEnd).trim();
    });
    return result;
  }

  value.split(' - ').forEach((part) => {
    const separatorIndex = part.indexOf(': ');
    if (separatorIndex === -1) return;
    result[part.slice(0, separatorIndex).trim()] = part
      .slice(separatorIndex + 2)
      .trim();
  });
  return result;
}

function buildSystemStatusReport(
  systemInfoData,
  systemStatusData,
  actionSchedulerData,
) {
  const lines = [];
  const queueStatus = systemStatusData.queueStatus || {};
  const systemInfo = systemInfoData || {};
  const wpInfo = parseCompositeField(systemInfo['WP info'], [
    'WP_MEMORY_LIMIT',
    'WP_MAX_MEMORY_LIMIT',
    'WP_DEBUG',
    'WordPress language',
    'WordPress timezone',
  ]);
  const phpInfo = parseCompositeField(systemInfo['PHP info'], [
    'PHP max_execution_time',
    'PHP memory_limit',
    'PHP upload_max_filesize',
    'PHP post_max_size',
  ]);
  const sendingServiceInfo = parseCompositeField(
    systemInfo['MailPoet Sending Service'],
    ['Is reachable', 'Ping response', 'API key state', 'Premium key state'],
  );
  const sendingDetails = parseCompositeField(
    systemInfo['MailPoet sending info'],
    [
      "Send all site's emails with",
      'Task Scheduler method',
      'Default FROM address',
      'Default Reply-To address',
      'Bounce Email Address',
    ],
  );
  const dataInconsistencyStatus = parseCompositeField(
    systemInfo['Data inconsistency status'],
  );
  const keyStateSummary = [
    sendingServiceInfo['API key state'],
    sendingServiceInfo['Premium key state']
      ? `premium: ${sendingServiceInfo['Premium key state']}`
      : null,
  ]
    .filter(Boolean)
    .join('; ');
  const premiumKeyValue = keyStateSummary
    ? `${systemInfo['MailPoet Premium/MSS key']} (${keyStateSummary})`
    : systemInfo['MailPoet Premium/MSS key'];
  const cronStatus = systemStatusData.cronStatus || {};
  const cronError = Array.isArray(cronStatus.last_error)
    ? cronStatus.last_error
        .map((error) => `${error.worker}: ${error.message}`)
        .join('; ')
    : cronStatus.last_error;

  const actionSchedulerVersion =
    actionSchedulerData?.version || MailPoet.I18n.t('none');
  const actionSchedulerStorage =
    actionSchedulerData?.storage || MailPoet.I18n.t('none');
  const actionSchedulerNextTrigger = actionSchedulerData?.latestTrigger
    ? MailPoet.Date.full(actionSchedulerData.latestTrigger)
    : MailPoet.I18n.t('unknown');
  const actionSchedulerLastTrigger = actionSchedulerData?.latestCompletedTrigger
    ? MailPoet.Date.full(actionSchedulerData.latestCompletedTrigger)
    : MailPoet.I18n.t('unknown');
  const actionSchedulerLastRun = actionSchedulerData?.latestCompletedRun
    ? MailPoet.Date.full(actionSchedulerData.latestCompletedRun)
    : MailPoet.I18n.t('unknown');

  let queueStatusText = MailPoet.I18n.t('unknown');
  if (queueStatus.status === 'paused')
    queueStatusText = MailPoet.I18n.t('paused');
  else if (queueStatus.status !== undefined)
    queueStatusText = MailPoet.I18n.t('running');
  const queueRetryAttempt =
    queueStatus.retryAttempt !== undefined && queueStatus.retryAttempt !== null
      ? queueStatus.retryAttempt
      : MailPoet.I18n.t('none');
  const queueRetryAt = queueStatus.retryAt
    ? formatTimestamp(queueStatus.retryAt)
    : MailPoet.I18n.t('unknown');
  const queueError = queueStatus.error?.errorMessage || MailPoet.I18n.t('none');
  const queueCounts = queueStatus.tasksStatusCounts || {};

  const activePlugins = Array.isArray(systemStatusData.activePlugins)
    ? systemStatusData.activePlugins
        .map((plugin) => ({
          name: `${plugin?.name || ''}`.trim(),
          author: `${plugin?.author || ''}`.trim(),
          version: `${plugin?.version || ''}`.trim(),
          versionLatest: plugin?.versionLatest
            ? `${plugin.versionLatest}`.trim()
            : null,
        }))
        .filter((plugin) => plugin.name && plugin.author && plugin.version)
    : [];

  lines.push('### MailPoet System Status Report ###');
  lines.push(`Generated: ${new Date().toISOString()}`);

  addSection(lines, 'Site & Account');
  addBullet(lines, 'Site name', systemInfo.name);
  addBullet(lines, 'Email', systemInfo.email);
  addBullet(lines, 'MailPoet Premium/MSS key', premiumKeyValue);
  addBullet(lines, 'Plugin installed at', systemInfo['Plugin installed at']);
  addBullet(
    lines,
    'Installed via WooCommerce onboarding wizard',
    formatYesNo(systemInfo['Installed via WooCommerce onboarding wizard']),
  );
  addBullet(
    lines,
    'Total subscribers',
    systemInfo['Total number of subscribers'],
  );

  addSection(lines, 'Versions');
  addBullet(lines, 'MailPoet Free', systemInfo['MailPoet Free version']);
  addBullet(lines, 'MailPoet Premium', systemInfo['MailPoet Premium version']);
  addBullet(lines, 'WordPress', systemInfo['WordPress version']);
  addBullet(lines, 'PHP', systemInfo['PHP version']);
  addBullet(lines, 'Database', systemInfo['Database version']);
  addBullet(lines, 'Action Scheduler', actionSchedulerVersion);

  addSection(lines, 'Environment');
  addBullet(lines, 'Web server', systemInfo['Web server']);
  addBullet(lines, 'Server OS', systemInfo['Server OS']);
  addBullet(lines, 'Multisite', systemInfo['Multisite environment?']);
  addBullet(lines, 'Current theme', systemInfo['Current Theme']);
  addBullet(lines, 'WordPress language', wpInfo['WordPress language']);
  addBullet(lines, 'WordPress timezone', wpInfo['WordPress timezone']);

  addSection(lines, 'WordPress & PHP Config');
  addBullet(lines, 'WP_MEMORY_LIMIT', wpInfo.WP_MEMORY_LIMIT);
  addBullet(lines, 'WP_MAX_MEMORY_LIMIT', wpInfo.WP_MAX_MEMORY_LIMIT);
  addBullet(lines, 'WP_DEBUG', wpInfo.WP_DEBUG);
  addBullet(lines, 'PHP max_execution_time', phpInfo['PHP max_execution_time']);
  addBullet(lines, 'PHP memory_limit', phpInfo['PHP memory_limit']);
  addBullet(
    lines,
    'PHP upload_max_filesize',
    phpInfo['PHP upload_max_filesize'],
  );
  addBullet(lines, 'PHP post_max_size', phpInfo['PHP post_max_size']);

  addSection(lines, `Active Plugins (${activePlugins.length})`);
  addActivePluginsList(lines, activePlugins);

  addSection(lines, 'Sending Configuration');
  addBullet(lines, 'Sending method', systemInfo['Sending Method']);
  addBullet(
    lines,
    "Send all site's emails with",
    sendingDetails["Send all site's emails with"],
  );
  addBullet(lines, 'Sending frequency', systemInfo['Sending Frequency']);
  addBullet(
    lines,
    'Default FROM address',
    formatOptional(sendingDetails['Default FROM address']),
  );
  addBullet(
    lines,
    'Default Reply-To address',
    formatOptional(sendingDetails['Default Reply-To address']),
  );
  addBullet(
    lines,
    'Bounce email address',
    formatOptional(sendingDetails['Bounce Email Address']),
  );

  addSection(lines, 'Connection to MailPoet Sending Service');
  addBullet(lines, 'Enabled', formatYesNo(systemStatusData.mss?.enabled));
  addBullet(lines, 'Reachable', formatYesNo(systemStatusData.mss?.isReachable));
  addBullet(lines, 'Ping response', sendingServiceInfo['Ping response']);
  addBullet(lines, 'API key state', sendingServiceInfo['API key state']);
  addBullet(
    lines,
    'Premium key state',
    sendingServiceInfo['Premium key state'],
  );

  addSection(lines, 'Task Scheduler / Cron');
  addBullet(lines, 'Status', cronStatus.status || MailPoet.I18n.t('unknown'));
  addBullet(
    lines,
    'Task Scheduler method',
    sendingDetails['Task Scheduler method'],
  );
  addBullet(lines, 'Ping URL', systemStatusData.cron?.url);
  addBullet(lines, 'Accessible', formatYesNo(cronStatus.accessible));
  addBullet(lines, 'Ping response', systemStatusData.cron?.pingResponse);
  addBullet(lines, 'Last updated', formatTimestamp(cronStatus.updated_at));
  addBullet(
    lines,
    'Last run started',
    formatTimestamp(cronStatus.run_started_at),
  );
  addBullet(
    lines,
    'Last run completed',
    formatTimestamp(cronStatus.run_completed_at),
  );
  addBullet(lines, 'Last seen error', cronError || MailPoet.I18n.t('none'));

  addSection(lines, 'Action Scheduler Status');
  addBullet(lines, 'Storage type', actionSchedulerStorage);
  addBullet(lines, 'Next trigger run', actionSchedulerNextTrigger);
  addBullet(lines, 'Last trigger run', actionSchedulerLastTrigger);
  addBullet(lines, 'Last worker run', actionSchedulerLastRun);

  addSection(lines, 'Sending Queue');
  addBullet(lines, 'Status', queueStatusText);
  addBullet(lines, 'Started at', formatTimestamp(queueStatus.started));
  addBullet(lines, 'Sent emails', queueStatus.sent || 0);
  addBullet(lines, 'Retry attempt', queueRetryAttempt);
  addBullet(lines, 'Retry at', queueRetryAt);
  addBullet(lines, 'Error', queueError);
  addBullet(lines, 'Total completed tasks', queueCounts.completed || 0);
  addBullet(lines, 'Total running tasks', queueCounts.running || 0);
  addBullet(lines, 'Total paused tasks', queueCounts.paused || 0);
  addBullet(lines, 'Total cancelled tasks', queueCounts.cancelled || 0);
  addBullet(lines, 'Total scheduled tasks', queueCounts.scheduled || 0);

  addSection(lines, 'Data Inconsistency');
  addBullet(
    lines,
    'Orphaned sending tasks',
    dataInconsistencyStatus['Orphaned sending tasks'],
  );
  addBullet(
    lines,
    'Orphaned sending task subscribers',
    dataInconsistencyStatus['Orphaned sending task subscribers'],
  );
  addBullet(
    lines,
    'Sending queue without newsletter',
    dataInconsistencyStatus['Sending queue without newsletter'],
  );
  addBullet(
    lines,
    'Orphaned subscriptions',
    dataInconsistencyStatus['Orphaned subscriptions'],
  );
  addBullet(lines, 'Orphaned links', dataInconsistencyStatus['Orphaned links']);
  addBullet(
    lines,
    'Orphaned newsletter posts',
    dataInconsistencyStatus['Orphaned newsletter posts'],
  );

  lines.push('');
  return lines.join('\n');
}

function downloadReport(reportText) {
  const date = new Date().toISOString().slice(0, 10);
  const fileName = `mailpoet-system-status-${date}.txt`;
  const blob = new Blob([reportText], { type: 'text/plain;charset=utf-8' });
  const url = window.URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = fileName;
  document.body.appendChild(anchor);
  anchor.click();
  document.body.removeChild(anchor);
  window.URL.revokeObjectURL(url);
}

function renderStatusMessage(
  status,
  successMessage,
  errorMessage,
  link,
  additionalInfo,
) {
  const noticeType = status ? 'success' : 'error';
  let noticeMessage = status ? successMessage : errorMessage;

  if (link) {
    noticeMessage = ReactStringReplace(
      noticeMessage,
      /\[link\](.*?)\[\/link\]/g,
      (match) => (
        <a className="mailpoet-text-link" href={link} key="kb-link">
          {match}
        </a>
      ),
    );
  }

  return (
    <div className={`mailpoet_notice notice inline notice-${noticeType}`}>
      <p>{noticeMessage}</p>
      {additionalInfo ? (
        <p>
          <i>{additionalInfo}</i>
        </p>
      ) : null}
    </div>
  );
}

function renderCronSection(data) {
  const status = data.cron.isReachable;
  const url = data.cron.url;
  const error = `${MailPoet.I18n.t(
    'systemStatusConnectionUnsuccessful',
  )} ${MailPoet.I18n.t('systemStatusCronConnectionUnsuccessfulInfo')}`;
  const success = MailPoet.I18n.t('systemStatusConnectionSuccessful');
  const additionalInfo = !status ? data.cron.pingResponse : null;

  return (
    <div>
      <h4>{MailPoet.I18n.t('systemStatusCronTitle')}</h4>
      <p>
        <a
          className="mailpoet-text-link"
          href={url}
          target="_blank"
          rel="noopener noreferrer"
        >
          {url}
        </a>
      </p>
      {renderStatusMessage(
        status,
        success,
        error,
        'https://kb.mailpoet.com/article/231-sending-does-not-work',
        additionalInfo,
      )}
    </div>
  );
}

function renderMSSSection(data) {
  const errorMessage = data.mss.enabled
    ? `${MailPoet.I18n.t(
        'systemStatusConnectionUnsuccessful',
      )} ${MailPoet.I18n.t('systemStatusMSSConnectionUnsuccessfulInfo')}`
    : MailPoet.I18n.t('systemStatusMSSConnectionCanNotConnect');
  const successMessage = data.mss.enabled
    ? MailPoet.I18n.t('systemStatusConnectionSuccessful')
    : MailPoet.I18n.t('systemStatusMSSConnectionCanConnect');
  return (
    <div>
      <h4>{MailPoet.I18n.t('systemStatusMSSTitle')}</h4>
      {renderStatusMessage(
        data.mss.isReachable,
        successMessage,
        errorMessage,
        'https://kb.mailpoet.com/article/319-known-errors-when-validating-a-mailpoet-key',
        null,
      )}
    </div>
  );
}

export function SystemStatus() {
  const reportId = 'mailpoet-system-status-report';
  const systemInfoData = window.systemInfoData;
  const systemStatusData = window.systemStatusData;
  const actionSchedulerData = window.actionSchedulerData;
  const [isReportVisible, setIsReportVisible] = useState(false);
  const [copyButtonLabel, setCopyButtonLabel] = useState(
    MailPoet.I18n.t('systemStatusCopyForSupport'),
  );
  const reportText = useMemo(
    () =>
      buildSystemStatusReport(
        systemInfoData,
        systemStatusData,
        actionSchedulerData,
      ),
    // eslint-disable-next-line react-hooks/exhaustive-deps -- data comes from window globals, stable for the page lifetime
    [],
  );

  const handleCopyForSupport = async () => {
    try {
      await copyToClipboard(
        reportId,
        (wasSuccessful) => {
          if (!wasSuccessful) {
            setCopyButtonLabel(MailPoet.I18n.t('copyToClipboardFailure'));
            return;
          }
          setCopyButtonLabel(MailPoet.I18n.t('copyToClipboardSuccess'));
          window.setTimeout(() => {
            setCopyButtonLabel(MailPoet.I18n.t('systemStatusCopyForSupport'));
          }, 3000);
        },
        true,
      );
    } catch {
      setCopyButtonLabel(MailPoet.I18n.t('copyToClipboardFailure'));
    }
  };

  return (
    <>
      <div className="mailpoet_notice notice inline">
        <p>
          {systemStatusData.mss.enabled
            ? MailPoet.I18n.t('systemStatusIntroCronMSS')
            : MailPoet.I18n.t('systemStatusIntroCron')}
        </p>
      </div>
      <div className="updated mailpoet-system-status-report inline">
        <p>{MailPoet.I18n.t('systemStatusGetReportIntro')}</p>
        <p className="submit">
          <button
            type="button"
            className="button button-primary"
            onClick={() => setIsReportVisible(true)}
          >
            {MailPoet.I18n.t('systemStatusGetReportTitle')}
          </button>
          <a
            className="button button-secondary"
            href="https://kb.mailpoet.com/article/understanding-the-mailpoet-system-status-page"
            target="_blank"
            rel="noopener noreferrer"
          >
            {MailPoet.I18n.t('systemStatusUnderstandingReport')}
          </a>
        </p>
        {isReportVisible ? (
          <div className="mailpoet-debug-report">
            <textarea readOnly id={reportId} value={reportText} />
            <p className="submit">
              <button
                type="button"
                className="button button-primary"
                onClick={() => downloadReport(reportText)}
              >
                {MailPoet.I18n.t('systemStatusDownloadReport')}
              </button>
              <button
                type="button"
                className="button"
                onClick={handleCopyForSupport}
              >
                {copyButtonLabel}
              </button>
            </p>
          </div>
        ) : null}
      </div>
      {renderCronSection(systemStatusData)}
      {renderMSSSection(systemStatusData)}
      <CronStatus status_data={systemStatusData.cronStatus} />
      <ActionSchedulerStatus {...actionSchedulerData} />
      {actionSchedulerData ? (
        <QueueStatus statusData={systemStatusData.queueStatus} />
      ) : null}
      <DataInconsistencies />
    </>
  );
}
