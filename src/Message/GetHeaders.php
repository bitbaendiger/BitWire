<?php

  declare (strict_types=1);

  namespace BitBaendiger\BitWire\Message;
  
  class GetHeaders extends GetBlocks {
    protected const PAYLOAD_COMMAND = 'getheaders';
    protected const PAYLOAD_MIN_VERSION = 31800;
  }
