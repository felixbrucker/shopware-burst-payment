module.exports = {
    extends: [
        'airbnb-base',
    ],
    plugins: [
        'import',
        'filenames',
        'promise',
        'simple-import-sort',
    ],
    rules: {
        'import/no-commonjs': 'error',
        'import/no-amd': 'error',
        'import/no-nodejs-modules': 'error',
        'no-console': 'error',
        'promise/avoid-new': 'off',
        'import/no-unresolved': 'off',
        'array-bracket-newline': ['error', 'consistent'],
        'brace-style': ['error', '1tbs', { allowSingleLine: false }],
        'class-methods-use-this': 'off',
        'curly': ['error', 'all'],
        'func-names': 'off',
        'function-paren-newline': ['error', 'multiline-arguments'],
        'indent': ['error', 4, { SwitchCase: 1 }],
        'max-len': ['error', 120],
        'multiline-ternary': ['error', 'never'],
        'newline-before-return': ['error'],
        'no-multiple-empty-lines': ['error', {
            max: 1,
            maxEOF: 1,
        }],
        'no-param-reassign': 'off',
        'object-curly-newline': ['error', {
            ObjectExpression: {
                multiline: true,
                minProperties: 2,
                consistent: true,
            },
            ImportDeclaration: {
                multiline: true,
                minProperties: 100,
                consistent: true,
            },
        }],
        'object-property-newline': ['error', {
            allowAllPropertiesOnSameLine: false,
        }],
        'prefer-destructuring': 'off',
        'quote-props': ['error', 'consistent-as-needed'],
        'no-shadow': 'off',
        'comma-dangle': ['error', {
            arrays: 'always-multiline',
            objects: 'always-multiline',
            imports: 'always-multiline',
            exports: 'always-multiline',
            functions: 'always-multiline',
        }],
        // See https://github.com/xjamundx/eslint-plugin-promise#rules
        'promise/catch-or-return': 'error',
        'promise/no-return-wrap': 'error',
        'promise/param-names': 'error',
        'promise/always-return': 'error',
        'promise/no-native': 'off',
        'promise/no-nesting': 'error',
        'promise/no-promise-in-callback': 'error',
        'promise/no-callback-in-promise': 'error',
        'promise/no-new-statics': 'error',
        'promise/no-return-in-finally': 'error',
        'promise/valid-params': 'error',
        'promise/prefer-await-to-then': 'error',
        'promise/prefer-await-to-callbacks': 'error',

        // Overwrite airbnb-base:
        'no-unused-vars': ['error', {
            // Added: Allow keeping unused variables when prefixed with an underscore.
            // Intended for unused arguments that precede used ones in an argument list.
            argsIgnorePattern: '^_',
            // Changed: Now, all unused arguments must be removed or prefixed.
            args: 'all',
            // Copied from airbnb-base:
            vars: 'all',
            ignoreRestSiblings: true,
        }],

        // Don't warn for no-use-before-define when accessing variables before their declaration in upper scopes. This
        // is necessary since we want to use the "const f = () => {}" function declaration style.
        'no-use-before-define': [
            'error',
            {
                functions: false,
                variables: false,
            },
        ],

        'max-classes-per-file': 'off',
        // Still allow string concatenation, which we prefer to overlong lines:
        'prefer-template': 'off',
        // Wrapping a line after an error may be necessary to keep within the line length limit:
        'implicit-arrow-linebreak': 'off',

        'import/extensions': 'off',

        // Named exports are usually better with regard to refactoring and grepability
        'import/prefer-default-export': 'off',

        // Replace import/order rule. It does not check for alphabetical sorting.
        // This may be improved in the future: <https://github.com/benmosher/eslint-plugin-import/issues/1311>
        'import/order': 'off',

        // Order imports by module type (relative, other module, builtin).
        // A similar rule may be integrated into @typescript-eslint in the future:
        // <https://github.com/typescript-eslint/typescript-eslint/pull/256>
        'simple-import-sort/sort': 'error',

        // Make sure filenames are kebab-case
        'filenames/match-regex': [
            'error',
            '^[0-9a-z-]+$',
        ],
    },
    overrides: [{
        files: [
            '*.config.js',
            '*.config.js.dist',
            '.eslintrc.js',
        ],
        rules: {
            'filenames/match-regex': [
                'error',
                '^[0-9a-z-]+\\.config$|\\.config.js$|^\\.eslintrc$',
            ],
            'import/no-extraneous-dependencies': [
                'error',
                { devDependencies: true },
            ],
            'import/no-commonjs': 'off',
            'import/no-nodejs-modules': 'off',
        },
    }, {
        files: [
            '.eslintrc.js',
        ],
        rules: {
            'filenames/match-regex': 'off',
        },
    }, {
        files: [
            '*.test.*',
            '*.testutil.*',
            '*.mock.*',
        ],
        extends: [
            'plugin:jest/all',
        ],
        rules: {
            // Tests may import devDependencies
            'import/no-extraneous-dependencies': [
                'error',
                {
                    devDependencies: true,
                },
            ],

            'jest/lowercase-name': 'off',
            'jest/no-hooks': 'off',
            'jest/prefer-inline-snapshots': 'off',

            // Make sure filenames are kebab-case
            'filenames/match-regex': [
                'error',
                '^[0-9a-z-]+(.test|.testutil|.mock)?$',
            ],

            // Tests may have empty functions.
            'no-empty-function': 'off',
        },
        overrides: [
            {
                files: ['*.testutil.*'],
                rules: {
                    'jest/no-export': 'off',
                    'jest/require-top-level-describe': 'off',
                    'jest/consistent-test-it': 'off',
                },
            },
        ],
    }],
    parserOptions: {
        ecmaVersion: 2018,
    },
    reportUnusedDisableDirectives: true,
    root: true,
};
