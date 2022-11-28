import { ComponentType, JSXElementConstructor } from 'react';
import { ErrorBoundary, ErrorBoundaryProps } from './error_boundary';

export const withBoundary = <P extends Record<string, unknown> | unknown>(
  Bound: JSXElementConstructor<P>,
  props?: ErrorBoundaryProps,
): ComponentType<P> =>
  function bounder(boundProps: P) {
    return (
      <ErrorBoundary {...props}>
        <Bound {...boundProps} />
      </ErrorBoundary>
    );
  };
