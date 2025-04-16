import { Component } from 'react';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { ListingHeadingStepsRoute } from 'newsletters/listings/heading-steps-route';
import _ from 'underscore';
import { NotificationScheduling } from 'newsletters/types/notification/scheduling.jsx';
import { Button } from 'common/button/button';
import { Heading } from 'common/typography/heading/heading';
import { Grid } from 'common/grid';
import { useNavigate } from 'react-router-dom';
import { GlobalContext, GlobalContextValue } from 'context';

interface NewsletterNotificationState {
  options: {
    intervalType: 'daily' | 'weekly' | 'monthly';
    timeOfDay: number;
    weekDay: number;
    monthDay: number;
    nthWeekDay: number;
  };
}

interface NewsletterNotificationProps {
  navigate: (path: string) => void;
}

type SuccessResponse = {
  data: {
    id: number;
  };
};

type ErrorResponse = {
  errors: Array<{
    message: string;
  }>;
};

const field = {
  name: 'options',
  type: 'reactComponent',
  component: NotificationScheduling,
};

class NewsletterNotificationComponent extends Component<
  NewsletterNotificationProps,
  NewsletterNotificationState
> {
  // eslint-disable-next-line react/static-property-placement
  declare context: GlobalContextValue;

  constructor(props: NewsletterNotificationProps) {
    super(props);
    this.state = {
      options: {
        intervalType: 'daily',
        timeOfDay: 0,
        weekDay: 1,
        monthDay: 0,
        nthWeekDay: 1,
      },
    };
  }

  componentDidMount() {
    if (
      window.location.search.includes('loadedvia=woo_multichannel_dashboard')
    ) {
      window.MailPoet.trackEvent(
        'MailPoet - WooCommerce Multichannel Marketing dashboard > Create post notification page',
        {
          'WooCommerce version': window.mailpoet_woocommerce_version,
        },
      );
    }
  }

  handleValueChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const state = this.state;
    state[event.target.name] = event.target.value;
    this.setState(state);
  };

  handleNext = () => {
    void MailPoet.Ajax.post<SuccessResponse>({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: _.extend({}, this.state, {
        type: 'notification',
        subject: __(
          'The last [newsletter:total] posts from our blog',
          'mailpoet',
        ),
      }),
    })
      .done((response) => {
        this.showTemplateSelection(response.data.id);
      })
      .fail((response: ErrorResponse) => {
        if (response.errors.length > 0) {
          this.context.notices.apiError(response, { scroll: true });
        }
      });
  };

  showTemplateSelection = (newsletterId: number) => {
    this.props.navigate(`/template/${newsletterId}`);
  };

  render() {
    return (
      <div>
        <ListingHeadingStepsRoute
          emailType="notification"
          automationId="post_notification_creation_heading"
        />

        <Grid.Column align="center" className="mailpoet-schedule-email">
          <Heading level={4}>
            {__('When to send this post notification email?', 'mailpoet')}
          </Heading>

          <NotificationScheduling
            item={this.state}
            field={field}
            onValueChange={this.handleValueChange}
          />

          <Button isFullWidth onClick={this.handleNext} type="button">
            {__('Next', 'mailpoet')}
          </Button>
        </Grid.Column>
      </div>
    );
  }
}

NewsletterNotificationComponent.contextType = GlobalContext;

export function NewsletterNotification() {
  const navigate = useNavigate();
  return <NewsletterNotificationComponent navigate={navigate} />;
}
