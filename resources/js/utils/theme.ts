import { usePreferredColorScheme, useStorage } from "@vueuse/core";
import { computed, watch } from "vue";

type themeOption = "system" | "light" | "dark";
const themeSetting = useStorage<themeOption>("theme", "system");
// reload page when themeSettingChanges
watch(
    themeSetting,
    () => {
        location.reload();
    }
)
const preferredColor = usePreferredColorScheme();
const theme = computed(() => {
    if(themeSetting.value === "system"){
        console.log(preferredColor.value);
        if (preferredColor.value === 'no-preference') {
            return 'dark';
        }
        return preferredColor.value;
    }
    return themeSetting.value
});

export { type themeOption, themeSetting, theme };
