import { App } from 'newsletter-editor/app';
import Marionette from 'backbone.marionette';
import _ from 'underscore';
import jQuery from 'jquery';
import { MailPoet } from 'mailpoet'; // eslint-disable-line func-names
import { __ } from '@wordpress/i18n';

var Module = {};

Module.HeadingView = Marionette.View.extend({
  // eslint-disable-next-line func-names
  getTemplate: function () {
    return window.templates.heading;
  },
  // eslint-disable-next-line func-names
  templateContext: function () {
    return {
      model: this.model.toJSON(),
      isWoocommerceTransactional: this.model.isWoocommerceTransactional(),
      isAutomationEmail: this.model.isAutomationEmail(),
      isConfirmationEmailTemplate: this.model.isConfirmationEmailTemplate(),
    };
  },
  // eslint-disable-next-line func-names
  events: function () {
    return {
      'change .mailpoet_input_title': _.partial(this.changeField, 'subject'),
      'change .mailpoet_input_preheader': _.partial(
        this.changeField,
        'preheader',
      ),
      'change #mailpoet_heading_email_type': (event) => {
        App.getChannel().trigger('changeWCEmailType', event.target.value);
      },
    };
  },
  // eslint-disable-next-line func-names
  changeField: function (field, event) {
    this.model.set(field, jQuery(event.target).val());
  },
});

// eslint-disable-next-line func-names
App.on('start', function (StartApp) {
  var model = StartApp.getNewsletter();

  StartApp._appView.showChildView(
    'headingRegion',
    new Module.HeadingView({ model: model }),
  );

  const subjectToolTip = document.getElementById(
    'tooltip-designer-subject-line',
  );
  const preheaderToolTip = document.getElementById(
    'tooltip-designer-preheader',
  );

  if (!model.isWoocommerceTransactional() && !model.isAutomationEmail()) {
    if (subjectToolTip) {
      MailPoet.helpTooltip.show(subjectToolTip, {
        tooltipId: 'tooltip-designer-subject-line-ti',
        tooltip: __(
          "You can add MailPoet shortcodes here. For example, you can add your subscribers' first names by using this shortcode: [subscriber:firstname | default:reader]. Simply copy and paste the shortcode into the field.",
          'mailpoet',
        ),
        place: 'right',
      });
    }

    if (preheaderToolTip) {
      MailPoet.helpTooltip.show(preheaderToolTip, {
        tooltipId: 'tooltip-designer-preheader-ti',
        tooltip:
          __(
            "This optional text will appear in your subscribers' inboxes, beside the subject line. Write something enticing!",
            'mailpoet',
          ) +
          ' ' +
          __(
            'Max length is 250 characters, however, we recommend 80 characters.',
            'mailpoet',
          ),
      });
    }
  }
});

export { Module as HeadingComponent };
