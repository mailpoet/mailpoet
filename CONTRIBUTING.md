# Contributing

## PHP Code
- Two spaces indentation.
- Space between keyword (if, for, switch...) and left bracket
- CamelCase for classes.
- camelCase for methods.
- snake_case for variables and class properties.
- Composition over Inheritance.
- Comments are a code smell. If you need to use a comment - see if same idea can be achieved by more clearly expressing code.
- Require other classes with 'use' at the beginning of the class file.
- Do not specify 'public' if method is public, it's implicit.
- Always use guard clauses.
- Ensure compatibility with PHP 7.1 and newer versions.
- Cover your code in tests.

## SCSS Code
- camelCase for file name
- Components files are prefixed with underscore, to indicate, that they aren't compiled separately.

## JS Code
- Javascript code should follow the [Airbnb style guide](https://github.com/airbnb/javascript).
- Prefer named export before default export in JS and TS files

## Disabling linting rules
- we want to avoid using `eslint-disable`
- if we have to use it we need to use a comment explaining why do we need it:
`/* eslint-disable no-new -- this class has a side-effect in the constructor and it's a library's. */`
- for PHP we do the same with the exception `// phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps` which for now doesn’t require an explanation

## Git flow
- Do not commit to master.
- Open a short-living feature branch.
- Open a pull request.
- Add Jira issue reference in the title of the Pull Request.
- Work on the pull request.
- Use the `./do qa` command to check your code style before pushing.
- Use good commit messages as explained here https://chris.beams.io/posts/git-commit
- Wait for review from another developer.

## Issues creation
- Issues are managed on Jira.
- Discuss issues on public Slack chats, discuss code in pull requests.
- Open a small Jira issue only when it has been discussed.

