# Contributing

## Code.
- Two spaces indentation.
- CamelCase for classes.
- camelCase for methods.
- snake_case for variables and class properties.
- Composition over Inheritance.
- Comments are a code smell. If you need to use a comment - see if same idea can be achieved by more clearly expressing code.
- Require other classes with 'use' at the beginning of the class file.
- Do not specify 'public' if method is public, it's implicit.
- Always use guard clauses.
- Ensure compatibility with PHP 5.3 and newer versions.
- Cover your code in tests.

Recommendations:
- Max line length at 80 chars.
- Keep classes under 100 LOC.
- Keep methods under 10 LOC.
- Pass no more than 4 parameters/hash keys into a method.
- Keep Pull Requests small, under 100 LOC changed.

## Git flow.
- Do not commit to master.
- Open a short-living feature branch.
- Open a pull request.
- Add Jira issue reference in the title of the Pull Request.
- Work on the pull request.
- Wait for review and confirmation from another developer before merging to master.
- Commit title no more than 80 chars, empty line after.
- Commit description as long as you want, 80 chars wrap.

## Issues creation.
- Issues are managed on Jira.
- Discuss issues on public Slack chats, discuss code in pull requests.
- Open a small Jira issue only when it has been discussed.
