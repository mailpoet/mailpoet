import ReactStringReplace from 'react-string-replace';
import { __ } from '@wordpress/i18n';
import { Heading } from 'common/typography/heading/heading';

function Faq() {
  const list: {
    slug: string;
    title: string;
    text: string;
    readMoreText: string;
    readMoreLink: string;
  }[] = [
    {
      slug: 'item-1',
      title: __(
        'What types of campaigns can I create with MailPoet?',
        'mailpoet',
      ),
      text: __(
        'MailPoet allows you to create five different types of emails: Newsletter, Welcome Email, Latest Post Notifications, Re-engagement Emails and WooCommerce.',
        'mailpoet',
      ),
      readMoreText: __('Read More', 'mailpoet'),
      readMoreLink:
        'https://kb.mailpoet.com/article/141-create-an-email-types-of-campaigns',
    },
    {
      slug: 'item-2',
      title: __('How do I send a newsletter?', 'mailpoet'),
      text: __(
        'You can manually create a standard newsletter to be sent immediately or scheduled to be sent at a later time. Simply go to MailPoet > Emails and click on the “+ New Email” button to select “Newsletter”.',
        'mailpoet',
      ),
      readMoreText: __('Read More', 'mailpoet'),
      readMoreLink:
        'https://kb.mailpoet.com/article/344-create-a-standard-newsletter',
    },
    {
      slug: 'item-3',
      title: __('Do I need a paid plan?', 'mailpoet'),
      text: __(
        "When you install the MailPoet plugin, you can use it for free up to 1,000 subscribers. If you have more than 1,000 subscribers, you'll need one of our paid plans: Creator, Business, or Agency. The best choice of plan type will depend on whether you want to send with our MailPoet Sending Service or your own sending method, as well as the number of sites you will be using MailPoet on.",
        'mailpoet',
      ),
      readMoreText: __('Read More', 'mailpoet'),
      readMoreLink:
        'https://kb.mailpoet.com/article/349-choosing-your-mailpoet-plan',
    },
    {
      slug: 'item-4',
      title: __('How do I import my customers from WooCommerce?', 'mailpoet'),
      text: __(
        'The WooCommerce Customers list is a list automatically created by MailPoet with all of your WooCommerce customers. It also includes “Guest" customers. If WooCommerce is active, users that installed or updated the plugin should have chosen if they wanted to add the customers as “Subscribed” or “Unsubscribed” to the WooCommerce Customers list.',
        'mailpoet',
      ),
      readMoreText: __('Read More', 'mailpoet'),
      readMoreLink:
        'https://kb.mailpoet.com/article/284-import-old-customers-to-the-woocommerce-customers-list',
    },
    {
      slug: 'item-5',
      title: __('How do I customize emails for my store?', 'mailpoet'),
      text: __(
        'You can create and send the following 4 WooCommerce Automatic emails with MailPoet: Abandoned Shopping Cart, First Purchase, Purchased In This Category, Purchased This Product. You can read more about each in our article.',
        'mailpoet',
      ),
      readMoreText: __('Read More', 'mailpoet'),
      readMoreLink:
        'https://kb.mailpoet.com/article/277-woocommerce-automatic-emails',
    },
  ];

  return (
    <section className="landing-faq">
      <div className="mailpoet-content-center landing-faq-header">
        <Heading level={2}>
          {' '}
          {__('Frequently asked questions', 'mailpoet')}{' '}
        </Heading>
        <p>
          {ReactStringReplace(
            __(
              "Here are some common questions on getting started. Can't find what you're looking for? [link]View all resources[/link]",
              'mailpoet',
            ),
            /\[link\](.*?)\[\/link\]/,
            (text) => (
              <a
                key={text}
                href="https://kb.mailpoet.com/"
                rel="noopener noreferrer"
                target="_blank"
              >
                {text}
              </a>
            ),
          )}
        </p>
      </div>

      <div className="mailpoet-content-center landing-faq-mobile">
        <Heading level={2}>{__('FAQ', 'mailpoet')}</Heading>
      </div>

      <div className="mailpoet-faq-accordion">
        {list.map((item) => (
          <details key={item.slug}>
            <summary>
              {' '}
              <strong> {item.title} </strong>{' '}
            </summary>
            <div className="content">
              <p>{item.text}</p>
              <p>
                <a
                  href={item.readMoreLink}
                  rel="noopener noreferrer"
                  target="_blank"
                >
                  {item.readMoreText}
                </a>
              </p>
            </div>
          </details>
        ))}
      </div>

      <div className="mailpoet-content-center landing-faq-mobile">
        <p>{__('Can’t find what you’re looking for?', 'mailpoet')}</p>
        <p>
          {ReactStringReplace(
            __('[link]View all resources[/link]', 'mailpoet'),
            /\[link\](.*?)\[\/link\]/,
            (text) => (
              <a
                key={text}
                href="https://kb.mailpoet.com/"
                rel="noopener noreferrer"
                target="_blank"
              >
                {text}
              </a>
            ),
          )}
        </p>
      </div>
    </section>
  );
}
Faq.displayName = 'Landingpage FAQ';

export { Faq };
