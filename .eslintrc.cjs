/* eslint-env node */
require("@rushstack/eslint-patch/modern-module-resolution")

module.exports = {
    extends: ['plugin:vue/vue3-essential', '@vue/eslint-config-typescript/recommended', '@vue/eslint-config-prettier'],
    rules: {
        'vue/multi-word-component-names': 'off',
        "@typescript-eslint/no-unused-vars": "off",
        "unused-imports/no-unused-imports": "error",
        "unused-imports/no-unused-vars": "error",
    },
    plugins: ['unused-imports'],
}
