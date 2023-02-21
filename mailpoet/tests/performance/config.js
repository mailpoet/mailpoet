export const baseURL = __ENV.URL || 'http://localhost:9500'; // eslint-disable-line
export const headlessSet = ['true', '1'].includes(
  `${__ENV.HEADLESS || 'true'}`.toLowerCase(), // eslint-disable-line
);
export const scenario = __ENV.SCENARIO; // eslint-disable-line
export const timeoutSet = '2m';

export const adminUsername = 'admin';
export const adminPassword = 'password';
export const adminEmail = 'test@test.com';

export const thinkTimeMin = '1';
export const thinkTimeMax = '4';
