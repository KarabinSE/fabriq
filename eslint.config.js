
import vue from 'eslint-plugin-vue';
import { defineConfig } from 'eslint/config';

const globals = {
    window: 'readonly',
    document: 'readonly',
    navigator: 'readonly',
    console: 'readonly'
};

export default defineConfig([
    ...vue.configs["flat/strongly-recommended"],
    {
        ignores: ['node_modules/', 'vendor/', 'dist/', 'build/', '.git/' ]
    },
    {
        files: ['**/*.js', '**/*.mjs', '**/*.cjs', '**/*.vue'],

        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals
        },

        rules: {
        // General JavaScript rules
            'indent': ['error', 4],
            // 'multiline-ternary': ['error', 'never'],
            'eqeqeq': ['error', 'always'],

            // Vue rules
            'vue/max-attributes-per-line': ['error', {
                singleline: 1,
                multiline: 1
            }],

            'vue/custom-event-name-casing': [
                'error',
                'kebab-case'
            ],

            'vue/multi-word-component-names': ['off'],

            'vue/component-name-in-template-casing': [
                'error',
                'PascalCase',
                {
                    registeredComponentsOnly: false
                }
            ],

            'vue/no-v-html': 'off',

            'vue/html-indent': [
                'error',
                4
            ]
        }
    }
]);
