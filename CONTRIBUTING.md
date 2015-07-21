# Contributing
## Coding guidelines

- Two spaces indentation, Ruby style.
- CamelCase for classes.
- snake_case for methods & variables.
- Max line length at 80 chars.
- Composition over Inheritance.
- ...

## Adding a JS dependency
In order to use a JS library (let's take Handlebars as an example), you need to follow these steps:

1. add "handlebars" as a dependency in the `package.json` file
```json
{
  "private": true,
  "dependencies": {
    "handlebars": "3.0.3",
  },
```
2. run `./do install` (the handlebars module will be added into the node_modules folder)
3. create a symlink to the file you want to use by running this command
```sh
# from the root of the project
$ cd assets/js/lib/
# /!\ use relative path to the node_modules folder
$ ln -nsf ../../../node_modules/handlebars/dist/handlebars.min.js handlebars.min.js
```
4. make sure to push the symlink onto the repository