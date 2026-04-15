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

    return (
        <div className="flex justify-center py-8">
            <span className={questionForm === 'character' ? 'text-7xl font-bold' : 'text-3xl font-medium'}>
                {getQuestionText(word, questionForm)}
            </span>
        </div>
    );
}
