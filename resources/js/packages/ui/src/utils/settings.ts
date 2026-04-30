export function getWeekStart() {
    const weekStart = window?.getWeekStartSetting() as string;

    if (!weekStart) {
        throw new Error(
            'Please make sure to provide the current user week start setting as a vue inject (week_start)'
        );
    }
    return weekStart;
}

const weekStartMap: Record<string, number> = {
    sunday: 0,
    monday: 1,
    tuesday: 2,
    wednesday: 3,
    thursday: 4,
    friday: 5,
    saturday: 6,
};

export function getWeekStartDayNumber(): number {
    return weekStartMap[getWeekStart()] ?? 1;
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
