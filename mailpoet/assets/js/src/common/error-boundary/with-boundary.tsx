import { ComponentType, JSXElementConstructor } from 'react';
import { ErrorBoundary, ErrorBoundaryProps } from './error-boundary';

export const withBoundary = <P extends Record<string, unknown> | object>(
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
