export const FeaturesController = (config) => ({
  isSupported: (feature) => {
    return config[feature] || false;
  },
});
