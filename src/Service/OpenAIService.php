<?php

namespace Src\Service;

use GuzzleHttp\Client;

class OpenAIService
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function sendRequest(array $prompts, string $role = 'user')
    {
        $httpClient = new Client();

        $body = $this->config['data'];

        $body['messages'] = [];

        foreach ($prompts as $prompt) {
            $body['messages'][] = [
                'role' => $role,
                'content' => $prompt,
            ];
        }

        $response = $httpClient->post(
            $this->config['url'],
            [
                'headers' => $this->config['headers'],
                'body' => json_encode($body),
            ]
        );

        $result = $response->getBody();

        $result = json_decode($result, true);

        return $result;
        // return $result['choices'][0]['message']['content'];
    }

    public function getModels()
    {
        $httpClient = new Client();

        $response = $httpClient->get(
            'https://api.openai.com/v1/models',
            [
                'headers' => $this->config['headers'],
            ]
        );

        $result = $response->getBody();

        $result = json_decode($result, true);

        return $result['data'];
    }
}