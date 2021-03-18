<?php

  declare (strict_types=1);

  namespace BitBaendiger\BitWire\Message;
  
  class MemPool extends Payload {
    protected const PAYLOAD_COMMAND = 'mempool';
    protected const PAYLOAD_MIN_VERSION = 60002;
    protected const PAYLOAD_HAS_DATA = false;
  }
