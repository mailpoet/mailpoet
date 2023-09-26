import { useEffect } from '@wordpress/element';
import { useSelect, dispatch } from '@wordpress/data';
import { store as editPostStore } from '@wordpress/edit-post';

// This custom hook disables the post editor welcome guide
export function useDisableWelcomeGuide() {
  const { isWelcomeGuideActive } = useSelect((select) => ({
    isWelcomeGuideActive: select(editPostStore).isFeatureActive(
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
      // The isFeatureActive accepts an attribute but typescript thinks it doesn't
      'welcomeGuide',
    ),
  }));

  useEffect(() => {
    if (isWelcomeGuideActive) {
      dispatch(editPostStore).toggleFeature('welcomeGuide');
    }
  }, [isWelcomeGuideActive]);
}
