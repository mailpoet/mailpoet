define('settings', ['react/addons', 'jquery', 'mailpoet'], function(React, jQuery, MailPoet) {

  var SettingsForm = React.createClass({
    mixins: [React.addons.LinkedStateMixin],
    load: function() {
      this.setState({ loading: true });

      MailPoet.Ajax.post({
        endpoint: 'settings',
        action: 'get',
        onSuccess: function(response) {
          // index settings by key
          var settings = {};

          jQuery.each(response, function(i, setting) {
            settings[setting.name] = setting.value;
          });

          this.setState({
            loading: false,
            settings: settings
          });
        }.bind(this)
      });
    },
    handleSubmit: function(e) {
      this.setState({ loading: true });

      e.preventDefault();

      // format data
      var settings = [];

      jQuery.each(this.state.settings, function(name, value) {
        settings.push({
          name: name,
          value: value
        });
      });

      MailPoet.Ajax.post({
        endpoint: 'settings',
        action: 'set',
        data: settings,
        onSuccess: function(response) {
          this.setState({ loading: false });
        }.bind(this)
      })
    },
    getInitialState: function() {
      return {
        loading: false,
        settings: {}
      };
    },
    linkSettingValue: function(key) {
      return {
        value: this.state['settings'][key],
        requestChange: function(newValue) {
          var settings = this.state.settings;
          settings[key] = newValue;
          this.setState({ 'settings': settings });
        }.bind(this)
      };
    },
    componentDidMount: function() {
      this.load();
    },
    render: function() {
      return (
        <form onSubmit={this.handleSubmit} className="mailpoet_settings_form">
          <p>
            <label>
              From name:
              <input
                type="text"
                valueLink={this.linkSettingValue('from_name')} />
            </label>
          </p>

          <p>
            <label>
              From email:
              <input
                type="text"
                valueLink={this.linkSettingValue('from_address')} />
            </label>
          </p>

          <p>
            <label>
              API key:
              <input
                type="text"
                valueLink={this.linkSettingValue('api_key')} />
            </label>
          </p>

          <input
            ref="submit"
            type="submit"
            className="button button-primary"
            disabled={this.state.loading}
            value="Save" />
        </form>
      );
    }
  });

  var element = jQuery('#mailpoet_settings');

  if(element.length > 0) {
    React.render(
      <SettingsForm />,
      element[0]
    );
  }
});
