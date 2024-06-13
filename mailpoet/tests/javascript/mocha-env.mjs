import * as chai from 'chai';
import register from '@babel/register';

// Register Babel to transpile our tests
register({ extensions: ['.js', '.jsx', '.ts', '.tsx'] });

// Set up chai.expect globally due issues with ES modules
// See https://github.com/chaijs/chai/issues/1568
global.expect = chai.expect;
