# @mailpoet/eslint-config

Shared ESLint flat configs.

| File                   | Use                                |
| ---------------------- | ---------------------------------- |
| `eslint-es5.config.js` | Legacy ES5 scripts                 |
| `eslint-es6.config.js` | ES6 and JSX                        |
| `eslint-ts.config.js`  | TypeScript                         |
| `globals.js`           | Re-export of the `globals` package |

Require the config you need from the consuming project and scope it with `files`. Each config is an array of flat-config objects.

## Bulk suppressions

ESLint can record existing violations in a suppressions file. A file keeps its current violation count for a rule, and lint fails when that count goes up.

Prefer fixing the code. Suppressions are for turning a rule on when there are too many existing violations to fix at once. A single justified exception belongs in an inline disable that says why.

ESLint reads and writes `eslint-suppressions.json` in the working directory. Pass `--suppressions-location` when the file lives somewhere else.

```bash
# Record every current violation
eslint --suppress-all "src/**/*.{js,jsx,ts,tsx}"

# Record current violations for one rule
eslint --suppress-rule no-unused-vars "src/**/*.{js,jsx,ts,tsx}"

# Same, with the suppressions file outside the working directory
eslint --suppressions-location path/to/eslint-suppressions.json --suppress-rule no-unused-vars "src/**/*.{js,jsx,ts,tsx}"

# Drop entries that no longer match a violation
eslint --prune-suppressions "src/**/*.{js,jsx,ts,tsx}"
```

Run prune after fixing suppressed code and commit the updated file. Unused entries fail the lint run until they are pruned.
