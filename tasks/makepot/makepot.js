/*
 MailPoet: MakePot
 - creates .pot file for translations
 - push to Transifex
 */
module.exports = function (grunt) {
    'use strict';

    // load multiple grunt tasks using globbing patterns
    require('load-grunt-tasks')(grunt);

    // get plugin path from options
    var base_path = grunt.option('base_path');

    if (base_path === undefined || grunt.file.exists(base_path) === false) {
        grunt.fail.fatal("Missing --base_path argument");
    } else {
        // configuration.
        grunt.initConfig({
            makepot: {
                target: {
                    options: {
                        cwd: '.', // base path where to look for translatable strings
                        domainPath: 'lang', // where to save the .pot
                        exclude: [
                            'build/.*',
                            'tests/.*',
                            'vendor/.*',
                            'tasks/.*'
                        ],
                        mainFile: 'index.php', // Main project file.
                        potFilename: 'wysija-newsletters.pot', // Name of the POT file.
                        potHeaders: {
                            poedit: true, // Includes common Poedit headers.
                            'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
                        },
                        type: 'wp-plugin', // Type of project (wp-plugin or wp-theme).
                        updateTimestamp: true, // Whether the POT-Creation-Date should be updated without other changes.
                        processPot: function (pot, options) {
                            pot.headers['report-msgid-bugs-to'] = 'http://support.mailpoet.com/';
                            pot.headers['last-translator'] = 'MailPoet i18n (https://www.transifex.com/organization/wysija)';
                            pot.headers['language-team'] = 'MailPoet i18n <https://www.transifex.com/organization/wysija>';
                            pot.headers['language'] = 'en_US';
                            return pot;
                        }
                    }
                }
            },
            shell: {
                options: {
                    stdout: true,
                    stderr: true
                },
                txpush: {
                    command: 'tx push -s' // push the resources (requires an initial resource set on TX website)
                },
                txpull: {
                    command: 'tx pull -a -f' // pull the .po files
                }
            }
        });

        // set base
        grunt.file.setBase(base_path);

        // Register tasks
        grunt.registerTask('default', function () {
            grunt.log.writeln(" x-----------------------------x");
            grunt.log.writeln(" |        MailPoet i18n        |");
            grunt.log.writeln(" x-----------------------------x");
            grunt.log.writeln(" \n Commands: \n");
            grunt.log.writeln(" grunt makepot  =  Generates the .pot file");
            grunt.log.writeln(" grunt pushpot  =  Pushes the .pot file to Transifex");
            grunt.log.writeln(" grunt update   =  Runs 'makepot' then 'pushpot'");
        });

        grunt.registerTask('pushpot', ['shell:txpush']);
        grunt.registerTask('update', ['makepot', 'shell:txpush']);
    }
};