export function openFeedback(): void {
    if (
        typeof window !== 'undefined' &&
        'showChatWindow' in window &&
        typeof window.showChatWindow === 'function'
    ) {
        window.showChatWindow();
    }
}
