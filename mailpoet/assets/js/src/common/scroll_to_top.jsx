import PropTypes from 'prop-types';
import { useEffect } from 'react';
import { withRouter } from 'react-router-dom';
import { withBoundary } from './error_boundary';

function ScrollToTopComponent({ children, location: { pathname } }) {
  useEffect(() => {
    window.scrollTo(0, 0);
  }, [pathname]);

  return children || null;
}

ScrollToTopComponent.propTypes = {
  location: PropTypes.shape({
    pathname: PropTypes.string.isRequired,
  }).isRequired,
  children: PropTypes.node.isRequired,
};

ScrollToTopComponent.displayName = 'ScrollToTopComponent';
export const ScrollToTop = withRouter(withBoundary(ScrollToTopComponent));
