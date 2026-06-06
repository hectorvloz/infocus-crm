<?php

namespace App\Support\Ai;

interface AiProvider
{
    /**
     * @param  array<int, array{role:string,content:string}>  $messages
     */
    public function chat(array $messages, array $settings): string;
}
