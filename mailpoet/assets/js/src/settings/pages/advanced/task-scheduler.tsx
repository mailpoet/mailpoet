import { __ } from '@wordpress/i18n';
import { Input } from 'common/form/input/input';
import { Radio } from 'common/form/radio/radio';
import { useSetting, useSelector } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export function TaskScheduler() {
  const [method, setMethod] = useSetting('cron_trigger', 'method');
  const paths = useSelector('getPaths')();

  return (
    <>
      <Label
        title={__('Newsletter task scheduler (cron)', 'mailpoet')}
        description={
          <>
            {__('Select what will activate your newsletter queue.', 'mailpoet')}{' '}
            <a
              className="mailpoet-link"
              href="https://kb.mailpoet.com/article/129-what-is-the-newsletter-task-scheduler"
              rel="noopener noreferrer"
              target="_blank"
            >
              {
                // translators: support article link label
                __('Read more.', 'mailpoet')
              }
            </a>
          </>
        }
        htmlFor="cron_trigger-method"
      />
      <Inputs>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="cron_trigger-method-action-scheduler"
            value="Action Scheduler"
            checked={method === 'Action Scheduler'}
            onCheck={setMethod}
            automationId="action_scheduler_cron_radio"
          />
          <label htmlFor="cron_trigger-method-action-scheduler">
            {__('Action Scheduler (recommended)', 'mailpoet')}
          </label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="cron_trigger-method-wordpress"
            value="WordPress"
            checked={method === 'WordPress'}
            onCheck={setMethod}
            automationId="wordress_cron_radio"
          />
          <label htmlFor="cron_trigger-method-wordpress">
            {__('Visitors to your website', 'mailpoet')}
          </label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="cron_trigger-method-cron"
            value="Linux Cron"
            checked={method === 'Linux Cron'}
            onCheck={setMethod}
            automationId="linux_cron_radio"
          />
          <label htmlFor="cron_trigger-method-cron">
            {__('Server side cron (Linux cron)', 'mailpoet')}
          </label>
        </div>
        {method === 'Linux Cron' && (
          <div className="mailpoet-settings-inputs-row">
            <div className="mailpoet-settings-inputs-row">
              {__(
                'To use this option please add this command to your crontab:',
                'mailpoet',
              )}
            </div>
            <Input
              dimension="small"
              type="text"
              readOnly
              value={`php ${paths.plugin}/mailpoet-cron.php ${paths.root}`}
            />
            <div className="mailpoet-settings-inputs-row">
              {__('With the frequency of running it every minute:', 'mailpoet')}
            </div>
            <Input dimension="small" type="text" readOnly value="*/1 * * * *" />
          </div>
        )}
      </Inputs>
    </>
  );
}
