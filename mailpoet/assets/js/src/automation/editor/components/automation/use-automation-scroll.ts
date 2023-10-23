import { MutableRefObject, useEffect } from 'react';

// Handle automation scrolling, including both X and Y axes simultaneously.
// Use animation frames to sync with browser repaints for smooth scrolling.
export const useAutomationScroll = (
  automationRef?: MutableRefObject<HTMLDivElement>,
): void =>
  useEffect(() => {
    const automation = automationRef?.current;
    if (!automation) {
      return undefined;
    }

    let frameId: number;
    const scrollHandler = (event) => {
      event.preventDefault();
      frameId = requestAnimationFrame(() => {
        automation.scrollLeft += event.deltaX;
        automation.scrollTop += event.deltaY;
      });
    };

    automation.addEventListener('wheel', scrollHandler, { passive: false });

    // cleanup
    return () => {
      automation.removeEventListener('wheel', scrollHandler);
      if (frameId) {
        cancelAnimationFrame(frameId);
      }
    };
  }, [automationRef]);
