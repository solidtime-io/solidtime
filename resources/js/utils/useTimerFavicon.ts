import { onMounted, onUnmounted, watch, type Ref } from 'vue';

export function useTimerFavicon(isActive: Ref<boolean>) {
    let restore = () => {};

    onMounted(() => {
        const icons = Array.from(
            document.querySelectorAll<HTMLLinkElement>('link[rel="icon"], link[rel="shortcut icon"]')
        ).map((link) => ({
            link,
            href: link.href,
            type: link.getAttribute('type'),
        }));

        restore = () => {
            for (const { link, href, type } of icons) {
                link.href = href;
                if (type === null) {
                    link.removeAttribute('type');
                } else {
                    link.type = type;
                }
            }
        };

        watch(
            isActive,
            (active) => {
                if (active) {
                    for (const { link } of icons) {
                        link.href = '/favicons/favicon-active.svg';
                        link.type = 'image/svg+xml';
                    }
                } else {
                    restore();
                }
            },
            { immediate: true }
        );
    });

    onUnmounted(() => restore());
}
