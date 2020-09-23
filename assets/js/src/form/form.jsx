import React from 'react';
import MailPoet from 'mailpoet';
import classNames from 'classnames';
import FormField from 'form/fields/field.jsx';
import jQuery from 'jquery';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { Button } from 'common';

class Form extends React.Component {
  constructor(props) {
    super(props);
    this.formRef = React.createRef();
    this.state = {
      loading: false,
      errors: [],
      item: {},
    };
  }

  componentDidMount() {
    if (this.props.params.id !== undefined) {
      this.loadItem(this.props.params.id);
    } else {
      setImmediate(() => {
        this.setState({
          item: jQuery('.mailpoet_form').mailpoetSerializeObject(),
        });
      });
    }
  }

  componentDidUpdate() {
    if (this.props.params.id === undefined && this.state.loading) {
      setImmediate(() => {
        this.setState({
          loading: false,
          item: {},
        });
      });
      if (this.props.item === undefined) {
        this.formRef.current.reset();
      }
    }
  }

  getValues = () => this.props.item || this.state.item;

  getErrors = () => this.props.errors || this.state.errors;

  loadItem = (id) => {
    this.setState({ loading: true });

    if (!this.props.endpoint) return;
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: this.props.endpoint,
      action: 'get',
      data: {
        id,
      },
    }).done((response) => {
      this.setState({
        loading: false,
        item: response.data,
      });
      if (typeof this.props.onItemLoad === 'function') {
        this.props.onItemLoad(response.data);
      }
    }).fail(() => {
      this.setState({
        loading: false,
        item: {},
      }, function failSetStateCallback() {
        this.props.history.push('/new');
      });
    });
  };

  handleSubmit = (e) => {
    e.preventDefault();

    // handle validation
    if (this.props.isValid !== undefined) {
      if (this.props.isValid() === false) {
        return;
      }
    }

    this.setState({ loading: true });

    // only get values from displayed fields
    const item = {};
    this.props.fields.forEach((field) => {
      if (field.fields !== undefined) {
        field.fields.forEach((subfield) => {
          item[subfield.name] = this.state.item[subfield.name];
        });
      } else {
        item[field.name] = this.state.item[field.name];
      }
    });
    // set id if specified
    if (this.props.params.id !== undefined) {
      item.id = this.props.params.id;
    }

    if (!this.props.endpoint) return;

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: this.props.endpoint,
      action: 'save',
      data: item,
    }).always(() => {
      this.setState({ loading: false });
    }).done(() => {
      if (this.props.onSuccess !== undefined) {
        this.props.onSuccess();
      } else {
        this.props.history.push('/');
      }

      if (this.props.params.id !== undefined) {
        this.props.messages.onUpdate();
      } else {
        this.props.messages.onCreate();
      }
    }).fail((response) => {
      if (response.errors.length > 0) {
        this.setState({ errors: response.errors });
      }
    });
  };

  handleValueChange = (e) => {
    const { name, value } = e.target;
    if (this.props.onChange) {
      return this.props.onChange(e);
    }
    this.setState((prevState) => {
      const item = prevState.item;
      const field = name;

      item[field] = value;

      return { item };
    });

    return true;
  };

  render() {
    let errors;
    if (this.getErrors() !== undefined) {
      errors = this.getErrors().map((error) => (
        <div className="mailpoet_notice notice inline error is-dismissible" key={`error-${error.message}`}>
          <p>{ error.message }</p>
        </div>
      ));
    }

    const formClasses = classNames(
      'mailpoet_form',
      { mailpoet_form_loading: this.state.loading || this.props.loading }
    );

    let beforeFormContent = false;
    let afterFormContent = false;

    if (this.props.beforeFormContent !== undefined) {
      beforeFormContent = this.props.beforeFormContent(this.getValues());
    }

    if (this.props.afterFormContent !== undefined) {
      afterFormContent = this.props.afterFormContent(this.getValues());
    }

    const fields = this.props.fields.map((field) => {
      // Compose an onChange handler from the default and custom one
      let onValueChange = this.handleValueChange;
      if (field.onBeforeChange) {
        onValueChange = (e) => {
          field.onBeforeChange(e);
          return this.handleValueChange(e);
        };
      }

      return (
        <FormField
          field={field}
          item={this.getValues()}
          onValueChange={onValueChange}
          key={`field-${field.name}`}
          automationId={field.automationId}
        />
      );
    });

    let actions = false;
    if (this.props.children) {
      actions = this.props.children;
    } else {
      actions = (
        <Button
          type="submit"
          isDisabled={this.state.loading}
        >
          {MailPoet.I18n.t('save')}
        </Button>
      );
    }

    return (
      <div>
        { beforeFormContent }
        <form
          id={this.props.id}
          ref={this.formRef}
          className={formClasses}
          onSubmit={
            (this.props.onSubmit !== undefined)
              ? this.props.onSubmit
              : this.handleSubmit
          }
          data-automation-id={this.props.automationId}
        >
          { errors }

          <div className="mailpoet-form-grid">
            {fields}

            <div className="mailpoet-form-actions">
              { actions }
            </div>
          </div>

        </form>
        { afterFormContent }
      </div>
    );
  }
}

Form.propTypes = {
  params: PropTypes.shape({
    id: PropTypes.string,
  }),
  item: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  errors: PropTypes.arrayOf(PropTypes.object),
  endpoint: PropTypes.string,
  fields: PropTypes.arrayOf(PropTypes.object),
  messages: PropTypes.shape({
    onUpdate: PropTypes.func,
    onCreate: PropTypes.func,
  }),
  loading: PropTypes.bool,
  children: PropTypes.array, // eslint-disable-line react/forbid-prop-types
  id: PropTypes.string,
  automationId: PropTypes.string,
  beforeFormContent: PropTypes.func,
  afterFormContent: PropTypes.func,
  onItemLoad: PropTypes.func,
  isValid: PropTypes.func,
  onChange: PropTypes.func,
  onSubmit: PropTypes.func,
  onSuccess: PropTypes.func,
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

Form.defaultProps = {
  params: {},
  errors: undefined,
  fields: undefined,
  item: undefined,
  onItemLoad: undefined,
  isValid: undefined,
  onSuccess: undefined,
  onChange: undefined,
  loading: false,
  beforeFormContent: undefined,
  afterFormContent: undefined,
  children: undefined,
  id: '',
  onSubmit: undefined,
  automationId: '',
  messages: {
    onUpdate: () => { /* no-op */ },
    onCreate: () => { /* no-op */ },
  },
  endpoint: undefined,
};

export default withRouter(Form);
