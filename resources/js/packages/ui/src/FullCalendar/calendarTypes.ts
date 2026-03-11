import type { TimeEntry, Project, Client, Task } from '@/packages/api/src';
import type { Dayjs } from 'dayjs';
import type { ActivityPeriod } from './activityTypes';

export const SLOT_HEIGHT = 25;
export const DRAG_THRESHOLD = 5;
export const TIME_AXIS_WIDTH = 48;

export interface CalendarEvent {
    id: string;
    timeEntry: TimeEntry;
    project?: Project;
    client?: Client;
    task?: Task;
    isRunning: boolean;
    durationMinutes: number;
    title: string;
    backgroundColor: string;
    borderColor: string;
    dayStart: Dayjs;
    dayEnd: Dayjs;
}

export interface DayEvent {
    event: CalendarEvent;
    top: number;
    height: number;
    left: string;
    width: string;
    isClippedStart: boolean;
    isClippedEnd: boolean;
}

export interface ActivityBox {
    dateStr: string;
    top: number;
    height: number;
    isIdle: boolean;
    period: ActivityPeriod;
}
