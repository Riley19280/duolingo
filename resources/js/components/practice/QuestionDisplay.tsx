import type { Word } from '@/types';
import { AudioButton } from './AudioButton';
import { getQuestionText } from './helpers';

export function QuestionDisplay({ word, questionForm }: { word: Word; questionForm: string }) {
    if (questionForm === 'audio') {
        return (
            <div className="flex justify-center py-6">
                <AudioButton ttsUrl={word.ttsUrl} />
            </div>
        );
    }

    const isCharacter = questionForm === 'character';
    const clickable = isCharacter && !!word.ttsUrl;

    return (
        <div className="flex justify-center py-8">
            <span
                className={`${isCharacter ? 'text-7xl font-bold' : 'text-3xl font-medium'}${clickable ? ' cursor-pointer select-none' : ''}`}
                onClick={() => clickable && new Audio(word.ttsUrl!).play()}
            >
                {getQuestionText(word, questionForm)}
            </span>
        </div>
    );
}
