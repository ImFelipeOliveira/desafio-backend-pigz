<?php

namespace App\Message;

final class SendFipeDataToSync
{
    /*
     * Add whatever properties and methods you need
     * to hold the data for this message class.
     */

    public function __construct(
        public readonly mixed $data
    ) {}
}
