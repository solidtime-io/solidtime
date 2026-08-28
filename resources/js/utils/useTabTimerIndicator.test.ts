import { describe, expect, it } from 'vitest';
import { buildTabTitle, resolveTabTimerIndicator } from './useTabTimerIndicator';

describe('resolveTabTimerIndicator', () => {
    it('is idle when no timer is running', () => {
        expect(resolveTabTimerIndicator(false, false)).toBe('idle');
        expect(resolveTabTimerIndicator(false, true)).toBe('idle');
    });

    it('is work when a non-break timer is running', () => {
        expect(resolveTabTimerIndicator(true, false)).toBe('work');
    });

    it('is break when a break timer is running', () => {
        expect(resolveTabTimerIndicator(true, true)).toBe('break');
    });
});

describe('buildTabTitle', () => {
    it('keeps the base title while idle', () => {
        expect(
            buildTabTitle(
                { indicator: 'idle', durationSeconds: 0, description: null },
                'Dashboard - solidtime'
            )
        ).toBe('Dashboard - solidtime');
    });

    it('shows duration and description while working', () => {
        expect(
            buildTabTitle(
                { indicator: 'work', durationSeconds: 5025, description: 'Fixing login bug' },
                'Dashboard - solidtime'
            )
        ).toBe('01:23:45 · Fixing login bug');
    });

    it('falls back to "No description" when the entry has none', () => {
        expect(
            buildTabTitle(
                { indicator: 'work', durationSeconds: 5, description: '   ' },
                'Dashboard - solidtime'
            )
        ).toBe('00:00:05 · No description');
    });

    it('labels break entries', () => {
        expect(
            buildTabTitle(
                { indicator: 'break', durationSeconds: 312, description: 'ignored' },
                'Dashboard - solidtime'
            )
        ).toBe('☕ 00:05:12 · Break');
    });

    it('never renders a negative duration', () => {
        expect(
            buildTabTitle(
                { indicator: 'work', durationSeconds: -3, description: 'Task' },
                'Dashboard - solidtime'
            )
        ).toBe('00:00:00 · Task');
    });
});
