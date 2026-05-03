<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class SentenceGenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return view('ai.SentenceGeneratorPrompt', [
            'words' => Auth::user()->words->filter(fn ($w) => $w->pivot->is_available)->pluck('text'),
        ])->render();
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'sentences' => $schema->array()->required()->items($schema->object(fn (JsonSchema $schema) => [
                'english' => $schema->string()->required(),
                'chinese' => $schema->string()->required(),
                'pinyin' => $schema->string()->required(),
            ])),
        ];
    }
}
