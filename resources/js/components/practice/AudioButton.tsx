import { Volume2 } from 'lucide-react';
import { useEffect, useRef } from 'react';

export function AudioButton({ ttsUrl }: { ttsUrl: string | null }) {
    const audioRef = useRef<HTMLAudioElement | null>(null);

    useEffect(() => {
        if (!ttsUrl) return;
        const audio = new Audio(ttsUrl);
        audioRef.current = audio;
        audio.play().catch(() => {});
        return () => {
            audio.pause();
        };
    }, [ttsUrl]);

    return (
        <button
            type="button"
            onClick={() => audioRef.current?.play()}
            className="flex h-28 w-28 cursor-pointer items-center justify-center rounded-full bg-primary/10 transition-colors hover:bg-primary/20"
        >
            <Volume2 className="size-14 text-primary" />
        </button>
    );
}
