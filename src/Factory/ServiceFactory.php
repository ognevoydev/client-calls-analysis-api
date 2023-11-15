<?php

namespace Src\Factory;

use DI\Attribute\Inject;
use Src\Service\ObjectStorageService;
use Src\Service\OpenAIService;
use Src\Service\TranscriptionService;

class ServiceFactory
{
    #[Inject]
    protected ObjectStorageService $objectStorageService;
    #[Inject]
    protected TranscriptionService $transcriptionService;
    #[Inject]
    protected OpenAIService $openAIService;

    public function getObjectStorageService()
    {
        return $this->objectStorageService;
    }

    public function getTranscriptionService()
    {
        return $this->transcriptionService;
    }

    public function getOpenAIService()
    {
        return $this->openAIService;
    }
}
