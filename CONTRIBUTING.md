# Contributing
## Coding guidelines

- Two spaces indentation, Ruby style.
- CamelCase for classes.
- camelCase for methods & variables.
- Max line length at 80 chars.
- Composition over Inheritance.
- Classes can be no longer than 100 LOC.
- Methods can be no longer than 5 LOC.
- Pass no more than 4 parameters/hash keys into a method.
- Routes can instantiate only one object. Therefore, views can only know about one instance variable and views should only send messages to that object ($object->collaborator->value is not allowed).
