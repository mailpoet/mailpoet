export default {
  presets: [
    ['@babel/preset-react', { runtime: 'automatic' }],
    '@babel/preset-env',
    '@babel/preset-typescript',
  ],
  plugins: [['@babel/plugin-transform-runtime']],
};
