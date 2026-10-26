import { useEffect, useState } from 'react';

type ColorMode = 'light' | 'dark';

const read = (): ColorMode =>
    document.documentElement.classList.contains('dark') ? 'dark' : 'light';

export function useColorMode(): ColorMode {
    const [mode, setMode] = useState<ColorMode>(read);

    useEffect(() => {
        const observer = new MutationObserver(() => setMode(read()));

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });

        return () => observer.disconnect();
    }, []);

    return mode;
}
