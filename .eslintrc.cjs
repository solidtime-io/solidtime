/* eslint-env node */
require("@rushstack/eslint-patch/modern-module-resolution")

module.exports = {
    extends: ['plugin:vue/vue3-essential', '@vue/eslint-config-typescript/recommended', '@vue/eslint-config-prettier'],
    rules: {
        'vue/multi-word-component-names': 'off',
    }
}
