<?php

namespace Src\Service;

class PromptGenerationService
{

    public function __construct()
    {

    }

    public function generate(string $text)
    {
        $prompts = [$text];
        // TODO - генерация промптов
        return $prompts;
    }
}