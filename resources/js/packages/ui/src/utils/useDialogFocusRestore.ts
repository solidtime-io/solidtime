/**
 * Restores focus to the element that was focused when a dialog opened.
 *
 * reka-ui remembers the active element at content mount as the dialog's
 * "trigger" (only when it is not the body) and refocuses it on every close,
 * but it never clears that value. A dialog opened while nothing is focused
 * (e.g. the command palette via Cmd+K from the body) therefore refocuses
 * whatever triggered a *previous* open. Bind these handlers to
 * `DialogContent`'s `open-auto-focus` / `close-auto-focus` events to restore
 * exactly the previously focused element, or nothing.
 *
 * A consumer handler that already called `preventDefault()` on
 * `close-auto-focus` keeps control; this composable then does nothing.
 */
export function useDialogFocusRestore() {
    let previouslyFocused: HTMLElement | null = null;

    function onOpenAutoFocus() {
        const active = document.activeElement;
        previouslyFocused =
            active instanceof HTMLElement && active !== document.body ? active : null;
    }

    function onCloseAutoFocus(event: Event) {
        const target = previouslyFocused;
        previouslyFocused = null;
        if (event.defaultPrevented) {
            return;
        }
        // Prevents both FocusScope's default restore and reka-ui's trigger refocus
        event.preventDefault();
        // Same tick reka-ui uses, so the dialog content is fully gone first
        setTimeout(() => {
            if (target?.isConnected) {
                target.focus({ preventScroll: true });
            }
        }, 0);
    }

    return { onOpenAutoFocus, onCloseAutoFocus };
}
