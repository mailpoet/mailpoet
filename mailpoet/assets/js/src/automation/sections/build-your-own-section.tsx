import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { MailPoet } from '../../mailpoet';

export function BuildYourOwnSection(): JSX.Element {
  const [itemOpen, setItemOpen] = useState('start-with-a-trigger');
  const list: { slug: string; title: string; text: string; image: string }[] = [
    {
      slug: 'start-with-a-trigger',
      title: __('Start with a trigger', 'mailpoet'),
      text: __(
        'Deliver relevant messages to your customers based on who they are and how they interact with your business.',
        'mailpoet',
      ),
      image: `${MailPoet.cdnUrl}automation/sections/start-with-a-trigger.png`,
    },
    {
      slug: 'customize-your-workflow',
      title: __('Customize your workflow', 'mailpoet'),
      text: __(
        'Choose steps and create a custom journey to best suit your needs.',
        'mailpoet',
      ),
      image: `${MailPoet.cdnUrl}automation/sections/customize-your-workflow.png`,
    },
    {
      slug: 'design-your-email',
      title: __('Design your email', 'mailpoet'),
      text: __(
        'Modify one of our pre-made email templates or create your own design.',
        'mailpoet',
      ),
      image: `${MailPoet.cdnUrl}automation/sections/design-your-email.png`,
    },
    {
      slug: 'start-engaging',
      title: __('Start engaging', 'mailpoet'),
      text: __(
        'Activate the automation and start engaging with your customers as they interact with your business.',
        'mailpoet',
      ),
      image: `${MailPoet.cdnUrl}automation/sections/start-engaging.png`,
    },
  ];

  const selectedItem = list.filter((item) => item.slug === itemOpen)[0];

  return (
    <section className="mailpoet-automation-section mailpoet-section-build-your-own">
      <div>
        <h2>{__('Build your own automation workflows', 'mailpoet')}</h2>
        <p>
          {__(
            'Create customized email sequences with our new automation editor.',
            'mailpoet',
          )}
        </p>
        <ol>
          {list.map((item, index) => (
            <li
              key={item.slug}
              className={itemOpen === item.slug ? 'open' : ''}
            >
              <div className="marker">
                {index < 10 ? `0${index + 1}` : index + 1}
              </div>
              <div>
                <button
                  type="button"
                  onClick={() => setItemOpen(item.slug)}
                  className="mailpoet-section-build-list-button"
                >
                  {item.title}
                </button>
                <p>{item.text}</p>
              </div>
            </li>
          ))}
        </ol>
      </div>
      <img src={selectedItem.image} alt={selectedItem.title} />
    </section>
  );
}
