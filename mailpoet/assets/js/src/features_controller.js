const FeaturesController = (config) => ({
  isSupported: (feature) => {
    return config[feature] || false;
  },
});

export default FeaturesController;
