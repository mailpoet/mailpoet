import { useEffect } from 'react';
import { useSelect } from '@wordpress/data';
import { store } from '../store';

function Fullscreen(): null {
  const isFullscreen = useSelect(
    (select) => select(store).isFullscreenEnabled(),
    [],
  );

  useEffect(() => {
    if (isFullscreen) {
      document.body.classList.add('is-fullscreen-mode');
    } else {
      document.body.classList.remove('is-fullscreen-mode');
    }
  }, [isFullscreen]);

  return null;
}

Fullscreen.displayName = 'Fullscreen';
export { Fullscreen };
