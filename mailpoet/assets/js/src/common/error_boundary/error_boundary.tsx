import { Children, Component } from '@wordpress/element';
import { ComponentType } from 'react';
import { getComponentDisplayName } from './utils';

type ErrorBoundaryState = {
  hasError: boolean;
  error?: string;
};

export type ErrorBoundaryProps = {
  onError?: (Error_Boundary_State) => ComponentType;
};

export class ErrorBoundary extends Component<
  ErrorBoundaryProps,
  ErrorBoundaryState
> {
  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = {
      hasError: false,
      error: '',
    };
  }

  static getDerivedStateFromError(error: Error & { fileName?: string }) {
    return {
      hasError: true,
      error: `${error?.toString()} \nFile name: ${
        error?.fileName ?? 'No fileName reported'
      } \nStack trace: ${error?.stack ?? 'No stack trace reported'}`,
    };
  }

  componentDidCatch(error, errorInfo) {
    // eslint-disable-next-line no-console
    console.log('logging', { error, errorInfo });
  }

  render() {
    const { onError } = this.props;
    const { hasError, error } = this.state;
    if (hasError) {
      if (onError) {
        return onError(this.state);
      }
      return (
        <>
          <h1>
            The application{' '}
            <strong>
              {Children.map(this.props.children, (child) =>
                getComponentDisplayName(child as ComponentType),
              ).join(', ')}{' '}
            </strong>
            encountered an error
          </h1>
          <p>
            Please report the following error to{' '}
            <a href="https://www.mailpoet.com/support/">MailPoet support</a>
          </p>
          <pre>{error}</pre>
        </>
      );
    }
    return this.props.children;
  }
}
