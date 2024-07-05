# Contributing

There is a `./do` command that helps with the development process. See [README](README.md) for more details.

## PHP Code

- Two spaces indentation.
- Space between keyword and left bracket (`if ()`, `for ()`, `switch ()`...).
- `CamelCase` for classes.
- `camelCase` for methods.
- `snake_case` for variables and class properties.
- Composition over Inheritance.
- Comments are a code smell. If you need to use a comment - see if same idea can be achieved by more clearly expressing code.
- Require other classes with `use` at the beginning of the class file.
- Always use guard clauses.
- Ensure compatibility with PHP 7.4 and newer versions.
- Cover your code in tests.

## SCSS Code

- `kebab-case` for file names.
- Components files are prefixed with underscore, to indicate, that they aren't compiled separately (`_new-component.scss`).

## JS Code

- Javascript code should follow the [Airbnb style guide](https://github.com/airbnb/javascript).
- Prefer named export before default export in JS and TS files
- Default to TypeScript for new files.

## Disabling linting rules

- We want to avoid using `eslint-disable`
- If we have to use it we need to use a comment explaining why do we need it:
  `/* eslint-disable no-new -- this class has a side-effect in the constructor and it's a library's. */`
- For PHP we do the same with the exception `// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps` which for now doesn’t require an explanation

## Git flow

- Do not commit to trunk.
- Open a short-living feature branch.
- Use good commit messages as explained here https://chris.beams.io/posts/git-commit. Include Jira ticket in the commit message.
- Use the `./do qa` command to check your code style before pushing.
- Create a pull request when finished. Include Jira ticket in the title of the pull request.
- Wait for review from another developer.

## Feature flags

We use feature flags to control the visibility of new features. This allows us to work on new features in smaller chunks before they are released to all customers.

- Feature flags can be enabled on the experimental page: `/admin.php?page=mailpoet-experimental`.
- New feature flags can be added in the class `FeaturesController`.
