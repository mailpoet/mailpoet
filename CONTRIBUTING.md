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
- for PHP we do the same with the exception `// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps` which for now doesn’t require an explanation

## Git flow

- Do not commit to trunk.
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

## Migration from IdiORM to Doctrine

MailPoet used to use [IdiORM](https://github.com/j4mie/idiorm) as its object-relational mapper (ORM), but the project was abandoned a while ago, so we started a migration to [Doctrine](https://www.doctrine-project.org/). This is a significant effort that has been going on for quite some time. Although you will still see parts of the code that use IdioORM, we ask that all new code be added using Doctrine instead.

All IdioORM models live in [mailpoet/lib/Models](https://github.com/mailpoet/mailpoet/tree/trunk/mailpoet/lib/Models), should be considered deprecated and shouldn't be used by new code. We are moving everything to Doctrine entities and some auxiliary code when needed. You can find Doctrine entities in [mailpoet/lib/Entities](https://github.com/mailpoet/mailpoet/tree/trunk/mailpoet/lib/Entities).
