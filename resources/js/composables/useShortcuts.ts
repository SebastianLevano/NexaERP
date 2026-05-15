import { onBeforeUnmount, onMounted } from 'vue';

interface Shortcut {
    key: string;
    mod?: boolean;
    shift?: boolean;
    when?: () => boolean;
    handler: (e: KeyboardEvent) => void;
}

export function useShortcuts(shortcuts: Shortcut[]) {
    function onKey(e: KeyboardEvent) {
        const isMac = navigator.platform.toLowerCase().includes('mac');
        for (const s of shortcuts) {
            const expectsMod = s.mod ?? false;
            const expectsShift = s.shift ?? false;
            const modPressed = isMac ? e.metaKey : e.ctrlKey;

            const keyMatches = e.key.toLowerCase() === s.key.toLowerCase();
            const modMatches = expectsMod ? modPressed : !modPressed;
            const shiftMatches = expectsShift ? e.shiftKey : !e.shiftKey;

            if (keyMatches && modMatches && shiftMatches) {
                if (s.when && !s.when()) continue;
                s.handler(e);
            }
        }
    }

    onMounted(() => window.addEventListener('keydown', onKey));
    onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
}
