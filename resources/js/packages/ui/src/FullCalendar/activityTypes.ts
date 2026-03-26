export interface WindowActivityInPeriod {
    appName: string;
    label: string | null;
    count: number;
    icon?: string | null;
}

export interface ActivityPeriod {
    start: string;
    end: string;
    isIdle: boolean;
    windowActivities?: WindowActivityInPeriod[];
}
