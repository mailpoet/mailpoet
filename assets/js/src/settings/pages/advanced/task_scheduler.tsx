import React from 'react';
import ReactStringReplace from 'react-string-replace';

import { t, onChange } from 'common/functions';
import { useSetting, useSelector } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function TaskScheduler() {
  const [method, setMethod] = useSetting('cron_trigger', 'method');
  const paths = useSelector('getPaths')();

  return (
    <>
      <Label
        title={t('taskCron')}
        description={(
          <>
            {t('taskCronDescription')}
            {' '}
            <a
              href="https://kb.mailpoet.com/article/129-what-is-the-newsletter-task-scheduler"
              data-beacon-article="57ce0a7a903360649f6e5703"
              rel="noopener noreferrer"
              target="_blank"
            >
              {t('readMore')}
            </a>
          </>
        )}
        htmlFor="cron_trigger-method"
      />
      <Inputs>
        <input
          type="radio"
          id="cron_trigger-method-wordpress"
          value="WordPress"
          checked={method === 'WordPress'}
          onChange={onChange(setMethod)}
        />
        <label htmlFor="cron_trigger-method-wordpress">
          {t('websiteVisitors')}
        </label>
        <br />
        <input
          type="radio"
          id="cron_trigger-method-mailpoet"
          value="MailPoet"
          checked={method === 'MailPoet'}
          onChange={onChange(setMethod)}
        />
        <label htmlFor="cron_trigger-method-mailpoet">
          {ReactStringReplace(t('mailpoetScript'),
            /\[link\](.*?)\[\/link\]/,
            (text) => (
              <a
                key={text}
                href="https://kb.mailpoet.com/article/131-hosts-which-mailpoet-task-scheduler-wont-work"
                data-beacon-article="57ce0b05c6979108399a0456"
                rel="noopener noreferrer"
                target="_blank"
              >
                {text}
              </a>
            ))}
        </label>
        <br />
        <input
          type="radio"
          id="cron_trigger-method-cron"
          value="Linux Cron"
          checked={method === 'Linux Cron'}
          onChange={onChange(setMethod)}
        />
        <label htmlFor="cron_trigger-method-cron">
          {t('serverCron')}
        </label>
        {method === 'Linux Cron' && (
          <>
            <br />
            {t('addCommandToCrontab')}
            <br />
            <input
              type="text"
              readOnly
              className="large-text"
              value={`php ${paths.plugin}/mailpoet-cron.php ${paths.root}`}
            />
            <br />
            {t('withFrequency')}
            <br />
            <input
              type="text"
              readOnly
              value="*/1 * * * *"
            />
          </>
        )}
      </Inputs>
    </>
  );
}
