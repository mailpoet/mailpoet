/**
 * Default values for various settings
 */

export const belowPostForm = {
  styles: {
    width: {
      unit: 'percent',
      value: 100,
    },
  },
};

const delayAndCookieExpiration = {
  formDelay: 15,
  formCookieExpiration: 7,
};

export const popupForm = {
  styles: {
    width: {
      unit: 'pixel',
      value: 560,
    },
  },
  ...delayAndCookieExpiration,
  animation: 'slideup',
};

export const fixedBarForm = {
  styles: {
    width: {
      unit: 'percent',
      value: 100,
    },
  },
  position: 'top',
  ...delayAndCookieExpiration,
  animation: 'slideup',
};

export const otherForm = {
  styles: {
    width: {
      unit: 'percent',
      value: 100,
    },
  },
};

export const slideInForm = {
  styles: {
    width: {
      unit: 'pixel',
      value: 560,
    },
  },
  position: 'right',
  ...delayAndCookieExpiration,
  animation: 'slideup',
};

export const formStyles = {
  formPadding: 20,
  inputPadding: 5,
  borderSize: 0,
  borderRadius: 0,
  alignment: 'left',
  closeButton: 'round_white',
};
