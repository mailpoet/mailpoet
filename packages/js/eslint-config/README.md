# @mailpoet/eslint-config

Shared ESLint flat configs.

| File                   | Use                                |
| ---------------------- | ---------------------------------- |
| `eslint-es5.config.js` | Legacy ES5 scripts                 |
| `eslint-es6.config.js` | ES6 and JSX                        |
| `eslint-ts.config.js`  | TypeScript                         |
| `globals.js`           | Re-export of the `globals` package |

Require the config you need from the consuming project and scope it with `files`. Each config is an array of flat-config objects.
