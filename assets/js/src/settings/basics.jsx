define(
  [
    'react',
    'mailpoet',
    'form/form.jsx',
    'form/fields/field.jsx'
  ],
  function(
    React,
    MailPoet
  ) {

    var BasicsForm = React.createClass({
      selectInput: function(input) {
        input.focus();
        input.select();
      },
      render: function() {
        console.log(this.props);
        return (
          <table className="form-table">
            <tbody>
              <tr>
                <th scope="row">
                  <label htmlFor="settings[notification_email]">
                    Email notifications
                    <p className="description">
                      Enter the email addresses that should receive
                      notifications (separate by comma).
                    </p>
                  </label>
                </th>
                <td>
                  <p>
                    <input
                      type="text"
                      className="regular-text"
                      id="settings[notification_email]"
                      name="mailpoet[settings][notification_email]"
                      defaultValue={ this.props.settings.notification_email }
                      placeholder="notification@mydomain.com" />
                  </p>
                  <p>
                    <label htmlFor="settings[notification_on_subscribe]">
                      <input
                        type="checkbox"
                        id="settings[notification_on_subscribe]"
                        name="mailpoet[settings][notification_on_subscribe]"
                        value="1" />
                      When someone subscribes
                    </label>
                  </p>
                  <p>
                    <label htmlFor="settings[notification_on_unsubscribe]">
                      <input type="checkbox"
                        id="settings[notification_on_unsubscribe]"
                        name="mailpoet[settings][notification_on_unsubscribe]"
                        value="1" />
                      When someone unsubscribes
                    </label>
                  </p>
                  <p>
                    <label htmlFor="settings[notification_daily_report]">
                      <input type="checkbox"
                        id="settings[notification_daily_report]"
                        name="mailpoet[settings][notification_daily_report]"
                        value="1" />
                      Daily summary of emails sent
                    </label>
                  </p>

                  <p>
                    <label htmlFor="settings[from_name]">
                      From
                    </label>
                    <input
                      type="text"
                      id="settings[from_name]"
                      name="mailpoet[settings][from_name]"
                      defaultValue={ this.props.settings.from_name }
                      placeholder="Your name" />
                    <input
                      type="text"
                      id="settings[from_email]"
                      name="mailpoet[settings][from_email]"
                      defaultValue={ this.props.settings.from_email }
                      placeholder="info@mydomain.com" />
                  </p>

                  <p>
                    <label htmlFor="settings[notification_reply_name]">
                      Reply-to
                    </label>
                    <input
                      type="text"
                      id="settings[notification_reply_name]"
                      name="mailpoet[settings][notification_reply_name]"
                      defaultValue={
                        this.props.settings.notification_reply_name
                      }
                      placeholder="Your name" />
                    <input
                      type="text"
                      id="settings[notification_reply_email]"
                      name="mailpoet[settings][notification_reply_email]"
                      defaultValue={
                        this.props.settings.notification_reply_email
                      }
                      placeholder="info@mydomain.com" />
                  </p>
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label htmlFor="settings[subscribe_on_comment]">
                    Subscribe in comments
                    <p className="description">
                      Visitors who submit a comment on a post can click on
                      a checkbox to subscribe.
                    </p>
                  </label>
                </th>
                <td>
                  <div id="mailpoet_subscribe_on_comment">
                    <p>
                      subscribe_on_comment_label
                    </p>
                  </div>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label htmlFor="settings[subscribe_on_register]">
                    Subscribe in registration form
                    <p className="description">
                        Allow users who register to your site to subscribe on
                        a list of your choice.
                    </p>
                  </label>
                </th>
                <td>
                  <p>
                    <input
                      data-toggle="mailpoet_subscribe_in_form"
                      type="checkbox"
                      value="1"
                      id="settings[subscribe_on_register]"
                      name="mailpoet[settings][subscribe_on_register]" />
                  </p>
                  <div id="mailpoet_subscribe_in_form">
                    <p>
                      <input
                        type="text"
                        id="settings[subscribe_on_register_label]"
                        name="mailpoet[settings][subscribe_on_register_label]"
                        value={ this.props.settings.subscribe_on_register_label } />
                    </p>
                    <p>
                      subscribe_on_register_lists
                    </p>
                  </div>
                  <em>Registration is disabled on this site.</em>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label htmlFor="settings[subscription_edit]">
                    Unsubscribe & Manage Subscription page
                    <p className="description">
                        The page your subscribers see when they click to "Unsubscribe" or "Manage your subscription" in your emails.
                    </p>
                  </label>
                </th>
                <td>
                  <p>
                    subscription_edit_page
                  </p>
                  <p>
                    <label>Subscribers can choose from these lists :</label>
                  </p>
                  <p>
                    subscription_edit_lists
                  </p>
                </td>
              </tr>

              <tr>
                <th scope="row">
                  <label>
                    Archive page shortcode
                    <p className="description">
                        Paste this shortcode in a page to display a list of past newsletters.
                    </p>
                  </label>
                </th>
                <td>
                  <p>
                    <input
                      type="text"
                      id="mailpoet_shortcode_archives"
                      value="[mailpoet_archive]"
                      onClick={ this.selectInput.bind(null, this) }
                      readOnly={ true } />
                  </p>
                  <p>
                    mailpoet_shortcode_archives_list
                  </p>
                </td>
              </tr>
              <tr>
                <th scope="row">
                  <label>
                    Shortcode to display total number of subscribers
                    <p className="description">
                      Paste this shortcode to display the number of confirmed subscribers in post or page.
                    </p>
                  </label>
                </th>
                <td>
                  <p>
                    <input
                      type="text"
                      id="mailpoet_shortcode_subscribers"
                      value="[mailpoet_subscribers_count]"
                      onClick={ this.selectInput.bind(null, this) }
                      readOnly={ true } />
                  </p>
                  <p>
                    mailpoet_shortcode_subscribers_list
                  </p>
                </td>
              </tr>
            </tbody>
          </table>
        );
      }
    });

    return BasicsForm;
  }
);