import { describe, expect, it, vi } from 'vitest';
import { useDialogFocusRestore } from './useDialogFocusRestore';

function closeEvent() {
    return new CustomEvent('focusScope.autoFocusOnUnmount', { cancelable: true });
}

describe('useDialogFocusRestore', () => {
    it('restores focus to the element focused when the dialog opened', () => {
        vi.useFakeTimers();
        const button = document.createElement('button');
        document.body.appendChild(button);
        button.focus();

        const { onOpenAutoFocus, onCloseAutoFocus } = useDialogFocusRestore();
        onOpenAutoFocus();
        button.blur();

        const event = closeEvent();
        onCloseAutoFocus(event);
        expect(event.defaultPrevented).toBe(true);
        vi.runAllTimers();
        expect(document.activeElement).toBe(button);

        button.remove();
        vi.useRealTimers();
    });

    it('focuses nothing when the dialog was opened with nothing focused', () => {
        vi.useFakeTimers();
        const stale = document.createElement('button');
        document.body.appendChild(stale);
        const { onOpenAutoFocus, onCloseAutoFocus } = useDialogFocusRestore();

        // First open from the button, then close
        stale.focus();
        onOpenAutoFocus();
        onCloseAutoFocus(closeEvent());
        vi.runAllTimers();
        stale.blur();

        // Second open from the body must not refocus the stale button
        onOpenAutoFocus();
        const event = closeEvent();
        onCloseAutoFocus(event);
        expect(event.defaultPrevented).toBe(true);
        vi.runAllTimers();
        expect(document.activeElement).toBe(document.body);

        stale.remove();
        vi.useRealTimers();
    });

    it('does not restore focus to an element that was removed', () => {
        vi.useFakeTimers();
        const button = document.createElement('button');
        document.body.appendChild(button);
        button.focus();
        const { onOpenAutoFocus, onCloseAutoFocus } = useDialogFocusRestore();
        onOpenAutoFocus();
        button.remove();

        onCloseAutoFocus(closeEvent());
        vi.runAllTimers();
        expect(document.activeElement).toBe(document.body);
        vi.useRealTimers();
    });

    it('leaves control to a consumer that already prevented the event', () => {
        vi.useFakeTimers();
        const button = document.createElement('button');
        document.body.appendChild(button);
        button.focus();
        const { onOpenAutoFocus, onCloseAutoFocus } = useDialogFocusRestore();
        onOpenAutoFocus();
        button.blur();

        const event = closeEvent();
        event.preventDefault();
        onCloseAutoFocus(event);
        vi.runAllTimers();
        expect(document.activeElement).toBe(document.body);

        button.remove();
        vi.useRealTimers();
    });
});
