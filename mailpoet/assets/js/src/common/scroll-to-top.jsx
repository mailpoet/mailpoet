import PropTypes from 'prop-types';
import { useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { withBoundary } from './error-boundary';

export function ScrollToTopComponent({ children }) {
  const location = useLocation();
  useEffect(() => {
    window.scrollTo(0, 0);
  }, [location.pathname]);

  return children || null;
}

ScrollToTopComponent.propTypes = {
  children: PropTypes.node.isRequired,
};

ScrollToTopComponent.displayName = 'ScrollToTopComponent';
export const ScrollToTop = withBoundary(ScrollToTopComponent);
