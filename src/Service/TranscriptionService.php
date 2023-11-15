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

        $sleepTime = 5; // 1 мин 1 канал - 10 сек
        $countQuery = 10; // Вычислить длину аудио

        for ($i = 1; $i <= $countQuery; $i++) {

            $responseTwo = $httpClient->get(
                'https://operation.api.cloud.yandex.net/operations/' . $operationId,
                [
                    'headers' => $this->config['headers'],
                ]
            );

            $resultTwo = $responseTwo->getBody();

            $resultTwo = json_decode($resultTwo, true);

            if (!empty($resultTwo['done']) && !empty($resultTwo['response'])) {
                $finalText = '';
                foreach ($resultTwo['response']['chunks'] as $chunk) {
                    $finalText .= "Собеседник " . $chunk["channelTag"] . ": " . $chunk['alternatives'][0]['text'] . "\n";
                }
                file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/finalText.txt", $finalText);
                // Запись текста в БД
                return $resultTwo;
            } elseif (!empty($resultTwo['error'])) {
                return $resultTwo['error']['message'];
            }

            sleep($sleepTime);
        }

        return ['result' => 'failure'];
    }
}