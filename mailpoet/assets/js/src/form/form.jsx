import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { Component, createRef } from 'react';
import { withRouter } from 'react-router-dom';
import jQuery from 'jquery';
import PropTypes from 'prop-types';

import { MailPoet } from 'mailpoet';
import { FormField } from 'form/fields/field.jsx';
import { Button, registerTranslations } from 'common';

/**
 * Translations need to be registered before any form rendering starts to make sure
 * they exist prior to any rendering
 */
registerTranslations();

class FormComponent extends Component {
  constructor(props) {
    super(props);
    this.formRef = createRef();
    this.state = {
      loading: false,
      errors: [],
      item: {},
    };
  }

  componentDidMount() {
    const { fields = undefined, params = {} } = this.props;

    if (params.id !== undefined) {
      this.loadItem(params.id);
    } else {
      setImmediate(() => {
        const defaultValues =
          jQuery('.mailpoet_form').mailpoetSerializeObject();
        const checkboxField =
          Array.isArray(fields) &&
          fields.length > 0 &&
          fields.find(
            (field) => field?.type === 'checkbox' && field?.isChecked,
          );
        if (checkboxField && checkboxField.name) {
          defaultValues[checkboxField.name] = '1';
        }
        this.setState({
          item: defaultValues,
        });
      });
    }
  }

  componentDidUpdate(prevProps) {
    const { item = undefined, location = {}, params = {} } = this.props;

    if (
      params.id === undefined &&
      prevProps.location.pathname !== location.pathname
    ) {
      setImmediate(() => {
        this.setState({
          loading: false,
          item: {},
        });
      });
      if (item === undefined) {
        this.formRef.current.reset();
      }
    }
  }

  getValues = () => this.props.item || this.state.item;

  getErrors = () => this.props.errors || this.state.errors;

  loadItem = (id) => {
    const {
      history,
      endpoint = undefined,
      onItemLoad = undefined,
    } = this.props;

    this.setState({ loading: true });
    if (!endpoint) return;
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint,
      action: 'get',
      data: {
        id,
      },
    })
      .done((response) => {
        this.setState({
          loading: false,
          item: response.data,
        });
        if (typeof onItemLoad === 'function') {
          onItemLoad(response.data);
        }
      })
      .fail(() => {
        this.setState(
          {
            loading: false,
            item: {},
          },
          () => {
            history.push('/lists');
          },
        );
      });
  };

  handleSubmit = (e) => {
    e.preventDefault();

    const {
      history,
      endpoint = undefined,
      fields = [],
      isValid = undefined,
      messages = {
        onUpdate: () => {},
        onCreate: () => {},
      },
      onSuccess = undefined,
      params = {},
    } = this.props;

    // handle validation
    if (typeof isValid === 'function') {
      if (isValid() === false) {
        return;
      }
    }

    this.setState({ loading: true });

    // only get values from displayed fields
    const item = {};
    fields.forEach((field) => {
      if (field.fields !== undefined) {
        field.fields.forEach((subfield) => {
          item[subfield.name] = this.state.item[subfield.name];
        });
      } else {
        item[field.name] = this.state.item[field.name];
      }
    });
    // set id if specified
    if (params.id !== undefined) {
      item.id = params.id;
    }

    if (!endpoint) return;

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint,
      action: 'save',
      data: item,
    })
      .always(() => {
        this.setState({ loading: false });
      })
      .done(() => {
        if (typeof onSuccess === 'function') {
          onSuccess();
        } else {
          history.push('/');
        }

        if (params.id !== undefined) {
          messages.onUpdate();
        } else {
          messages.onCreate();
        }
      })
      .fail((response) => {
        if (!(response && response.errors && response.errors.length)) return;

        if (JSON.stringify(response.errors).includes('reinstall_plugin')) {
          MailPoet.Notice.showApiErrorNotice(response);
        } else {
          this.setState({ errors: response.errors });
        }
      });
  };

  handleValueChange = (e) => {
    const { onChange = undefined } = this.props;
    // Because we need to support events that don't have the original event we need to check that the property target exists
    const { name, value } = Object.prototype.hasOwnProperty.call(e, 'target')
      ? e.target
      : e;
    if (typeof onChange === 'function') {
      return onChange(e);
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
    const {
      children,
      afterFormContent: propsAfterFormContent = undefined,
      beforeFormContent: propsBeforeFormContent = undefined,
      onSubmit = undefined,
      fields: propsFields = [],
      id = '',
    } = this.props;
    let errors;
    if (this.getErrors() !== undefined) {
      errors = this.getErrors().map((error) => (
        <div
          className="mailpoet_notice notice inline error is-dismissible"
          key={`error-${error.message}`}
        >
          <p>{error.message}</p>
        </div>
      ));
    }

    const formClasses = classnames('mailpoet_form', {
      mailpoet_form_loading: this.state.loading || this.props.loading,
    });

    let beforeFormContent = false;
    let afterFormContent = false;

    if (typeof propsBeforeFormContent === 'function') {
      beforeFormContent = propsBeforeFormContent(this.getValues());
    }

    if (typeof propsAfterFormContent === 'function') {
      afterFormContent = propsAfterFormContent(this.getValues());
    }

    const fields = propsFields.map((field) => {
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
    if (children) {
      actions = children;
    } else {
      actions = (
        <Button type="submit" isDisabled={this.state.loading}>
          {__('Save', 'mailpoet')}
        </Button>
      );
    }

    return (
      <div>
        <div className="mailpoet-form-content-around">{beforeFormContent}</div>
        <form
          id={id}
          ref={this.formRef}
          className={formClasses}
          onSubmit={
            typeof onSubmit === 'function' ? onSubmit : this.handleSubmit
          }
          data-automation-id={this.props.automationId}
        >
          {errors}

          <div className="mailpoet-form-grid">
            {fields}

            <div className="mailpoet-form-actions">{actions}</div>
          </div>
        </form>
        <div className="mailpoet-form-content-around">{afterFormContent}</div>
      </div>
    );
  }
}

FormComponent.propTypes = {
  params: PropTypes.shape({
    id: PropTypes.string,
  }),
  location: PropTypes.shape({
    pathname: PropTypes.string,
  }),
  item: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  errors: PropTypes.arrayOf(PropTypes.object), // eslint-disable-line react/forbid-prop-types
  endpoint: PropTypes.string,
  fields: PropTypes.arrayOf(PropTypes.object), // eslint-disable-line react/forbid-prop-types
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

export const Form = withRouter(FormComponent);
