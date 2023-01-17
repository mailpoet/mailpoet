export const baseURL = __ENV.URL || 'http://localhost:9500';
export const headlessSet = ['true', '1'].includes(
  `${__ENV.HEADLESS || 'true'}`.toLowerCase(),
);
export const scenario = __ENV.SCENARIO;

export const adminUsername = 'admin';
export const adminPassword = 'password';

export const thinkTimeMin = '1';
export const thinkTimeMax = '4';
