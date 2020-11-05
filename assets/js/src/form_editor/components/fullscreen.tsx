import { useEffect } from 'react';
import { useSelect } from '@wordpress/data';

const Fullscreen = () => {
  const isFullscreen = useSelect(
    (select) => select('mailpoet-form-editor').isFullscreenEnabled(),
    []
  );

  useEffect(() => {
    if (isFullscreen) {
      document.body.classList.add('is-fullscreen-mode');
    } else {
      document.body.classList.remove('is-fullscreen-mode');
    }
  }, [isFullscreen]);

  return (null);
};

export default Fullscreen;
