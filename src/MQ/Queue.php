<?php

namespace Src\MQ;

class Queue
{
    public const UPLOAD = 1;
    public const TRANSCRIBE_IN = 2;
    public const TRANSCRIBE_PROCESS = 3;
    public const GENERATE_PROMPT = 4;
    public const SEND_REQUEST = 5;
}