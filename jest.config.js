const { defaults: tsjPreset } = require('ts-jest/presets');

module.exports = {
  preset: 'ts-jest',
  transform: {
    // For transformations with .ts and .tsx files.
    ...tsjPreset.transform,
    // For transformations with .js, .jsx and .mjs files.
    '^.+\\.(js|jsx|mjs)$': 'babel-jest',
  },
  /*
   * Some packages that @wordpress/components uses ship only ES modules, which
   * Jest can only run transformed: uuid, and those with .mjs files.
   */
  transformIgnorePatterns: ['/node_modules/(?!(.*/)?uuid/).+(?<!\\.mjs)$'],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/$1',
    '\\.(css|less|scss|sass)$': 'identity-obj-proxy',
  },
  setupFilesAfterEnv: ['@testing-library/jest-dom'],
  testEnvironment: 'jsdom',
};
