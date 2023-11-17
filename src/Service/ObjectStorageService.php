<?php

namespace Src\Service;

use Aws\S3\S3Client;

class ObjectStorageService
{
    protected array $config;

    protected S3Client $storage;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->storage = new S3Client($this->config);
    }

    public function upload(string $path, string $bucket)
    {
        $result = [];

        $response = $this->storage->upload(
            $bucket,
            basename($path),
            fopen($path, 'r')
        );

        $response = $response->toArray();

        $result['objectURL'] = $response['ObjectURL'];

        $result['fileName'] = basename($path);

        $result['bucket'] = $bucket;

        return $result;
    }
}