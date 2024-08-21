export function getWeekStart() {
    const weekStart = window?.getWeekStartSetting() as string;

    if (!weekStart) {
        throw new Error(
            'Please make sure to provide the current user week start setting as a vue inject (week_start)'
        );
    }
    return weekStart;
}
export function getUserTimezone() {
    const timezone = window?.getTimezoneSetting() as string;
    if (!timezone) {
        throw new Error(
            'Please make sure to provide the current user timezone as a vue inject (timezone)'
        );
    }
    return timezone;
}
