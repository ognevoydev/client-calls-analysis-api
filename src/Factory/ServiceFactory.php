<?php

namespace Src\Factory;

use DI\Attribute\Inject;
use Src\Service\ObjectStorageService;
use Src\Service\OpenAIService;
use Src\Service\PromptGenerationService;
use Src\Service\TranscriptionService;

class ServiceFactory
{
    #[Inject]
    protected ObjectStorageService $objectStorageService;
    #[Inject]
    protected TranscriptionService $transcriptionService;
    #[Inject]
    protected PromptGenerationService $promptGenerationService;
    #[Inject]
    protected OpenAIService $openAIService;

    public function __construct()
    {

    }

    public function getObjectStorageService(): ObjectStorageService
    {
        return $this->objectStorageService;
    }

    public function getTranscriptionService(): TranscriptionService
    {
        return $this->transcriptionService;
    }

    public function getPromptGenerationService(): PromptGenerationService
    {
        return $this->promptGenerationService;
    }

    public function getOpenAIService(): OpenAIService
    {
        return $this->openAIService;
    }
}
