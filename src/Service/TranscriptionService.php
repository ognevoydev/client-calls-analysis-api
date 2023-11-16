<?php

namespace Src\Service;

use GuzzleHttp\Client;

class TranscriptionService
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function transcribe(string $audioUrl)
    {
        $httpClient = new Client();

        $data = $this->config['data'];
        $data['audio']['uri'] = $audioUrl;

        $response = $httpClient->post(
            $this->config['url'],
            [
                'headers' => $this->config['headers'],
                'body' => json_encode($data),
            ]
        );

        $result = $response->getBody();

        $result = json_decode($result, true);

        $operationId = $result["id"];

        return $operationId;
    }

    public function getStatus($operationId)
    {
        $httpClient = new Client();

        $response = $httpClient->get(
            'https://operation.api.cloud.yandex.net/operations/' . $operationId,
            [
                'headers' => $this->config['headers'],
            ]
        );

        $result = $response->getBody();

        $result = json_decode($result, true);

        if (!empty($result['done']) && !empty($result['response'])) {
            $finalText = '';
            foreach ($result['response']['chunks'] as $chunk) {
                $finalText .= "Собеседник " . $chunk["channelTag"] . ": " . $chunk['alternatives'][0]['text'] . "\n";
            }
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/finalText.txt", $finalText);
            // Запись текста в БД
            return $result;
        } elseif (!empty($result['error'])) {
            return $result['error']['message'];
        }

        return ['result' => 'failure'];
    }
}