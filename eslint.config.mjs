import vue from 'eslint-plugin-vue'

export default [
    ...vue.configs['flat/recommended'],
    {
        ignores: ['vendor', 'node_modules', 'public', 'bootstrap/ssr'],
    },
    {
        ignores: ['resources/js/components/ui/*'],
        rules: {
            'vue/require-default-prop': 'off',
        },
    },
    {
        rules: {
            'vue/no-v-text-v-html-on-component': ['warn', {
                'allow': ['H1', 'H2', 'H3', 'H4', 'H5', 'H6'],
                'ignoreElementNamespaces': false,
            }],

            indent: ['error', 4, {
                SwitchCase: 1,
            }],

            'linebreak-style': ['error', 'unix'],
            quotes: ['error', 'single'],
            semi: ['error', 'never'],
            eqeqeq: ['error'],
            'no-nested-ternary': 'error',
            'comma-dangle': ['error', 'always-multiline'],


            'object-curly-spacing': ['error', 'always', {
                objectsInObjects: true,
            }],

            'comma-spacing': ['error', {
                before: false,
                after: true,
            }],

            'object-property-newline': ['error', {
                'allowAllPropertiesOnSameLine': false,
            }],

            'object-curly-newline': ['error', {
                'ObjectExpression': 'always',
                'ObjectPattern': {
                    'multiline': true,
                },
                'ImportDeclaration': {
                    'multiline': true,
                    'minProperties': 5,
                },
                'ExportDeclaration': {
                    'multiline': true,
                    'minProperties': 3,
                },
            }],

            'key-spacing': ['error', {
                singleLine: {
                    beforeColon: false,
                    afterColon: true,
                },

                multiLine: {
                    beforeColon: false,
                    afterColon: true,
                },
            }],

            'padding-line-between-statements': ['error', {
                blankLine: 'always',
                prev: '*',
                next: 'block',
            }, {
                blankLine: 'always',
                prev: 'block',
                next: '*',
            }, {
                blankLine: 'always',
                prev: '*',
                next: 'block-like',
            }, {
                blankLine: 'always',
                prev: 'block-like',
                next: '*',
            }, {
                blankLine: 'always',
                prev: '*',
                next: 'export',
            }, {
                blankLine: 'always',
                prev: '*',
                next: 'return',
            }, {
                blankLine: 'always',
                prev: '*',
                next: 'function',
            }, {
                blankLine: 'always',
                prev: ['const', 'let', 'var'],
                next: '*',
            }, {
                blankLine: 'any',
                prev: ['const', 'let', 'var'],
                next: ['const', 'let', 'var'],
            }],

            'vue/order-in-components': ['error'],

            'vue/html-indent': ['error', 4, {
                attribute: 1,
                baseIndent: 1,
                closeBracket: 0,
                alignAttributesVertically: false,
                ignores: [],
            }],

            'vue/block-lang': ['error', {
                'script': {
                    'allowNoLang': true,
                    'lang': 'js',
                },
            }],

            'vue/max-attributes-per-line': ['error', {
                singleline: {
                    max: 2,
                },

                multiline: {
                    max: 1,
                },
            }],

            'vue/new-line-between-multi-line-property': ['error'],

            'vue/component-name-in-template-casing': ['error', 'PascalCase', {
                registeredComponentsOnly: false,
                ignores: ['draggable'],
            }],

            'vue/padding-line-between-blocks': ['error', 'always'],
            'vue/multi-word-component-names': ['off'],

            'vue/block-order': ['error', {
                order: ['template', 'script', 'style'],
            }],

            'no-trailing-spaces': ['error'],
        },
    },
]
