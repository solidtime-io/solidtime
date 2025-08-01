import { usePreferredColorScheme, useStorage } from '@vueuse/core';
import { computed, watch } from 'vue';

type themeOption = 'system' | 'light' | 'dark';
const themeSetting = useStorage<themeOption>('theme', 'system');
const preferredColor = usePreferredColorScheme();
const theme = computed(() => {
    if (themeSetting.value === 'system') {
        console.log(preferredColor.value);
        if (preferredColor.value === 'no-preference') {
            return 'dark';
        }
        return preferredColor.value;
    }
    return themeSetting.value;
});

function useTheme() {
    document.documentElement.classList.add(theme.value);
    watch(theme, (newTheme, oldTheme) => {
        document.documentElement.classList.remove(oldTheme);
        document.documentElement.classList.add(newTheme);
    });
}

export { type themeOption, themeSetting, theme, useTheme };
