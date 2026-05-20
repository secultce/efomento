import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import prettierConfig from 'eslint-config-prettier';
import globals from 'globals';
import pluginCypress from 'eslint-plugin-cypress';

export default [
    {
        ignores: [
            'public/**',
            'vendor/**',
            'node_modules/**',
            'storage/**',
            'bootstrap/ssr/**',
            'resources/js/ziggy.js',
        ],
    },
    {
        files: ['cypress/**/*.{js,cjs,mjs}'],
        ...pluginCypress.configs.recommended,
    },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    prettierConfig,
    {
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                ...globals.browser,
                tinymce: 'readonly',
                route: 'readonly',
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            // Vuetify usa modificadores em v-slot (ex: #item.name) que o plugin Vue não reconhece
            'vue/valid-v-slot': ['error', { allowModifiers: true }],
            'no-console': ['warn', { allow: ['error'] }],
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
        },
    },
];
