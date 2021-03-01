<?php

  declare (strict_types=1);

  use PHPUnit\Framework\TestCase;

  final class TransactionTest extends TestCase {
    public function testZerocoinCoinbase () : void {
      $this->assertIsObject (
        \BitBaendiger\BitWire\Transaction::fromHex (
          /* Version  */ '01000000' .
          /* Input#   */ '01' .
          /* Prevout  */ '0000000000000000000000000000000000000000000000000000000000000000' .
          /* Previdx  */ 'ffffffff' .
          /* Script   */ '05024c540101' .
          /* Sequence */ 'ffffffff' .
          /* Output#  */ '01' .
          /* Amount   */ '0000000000000000' .
          /* Script   */ '00' .
          /* Locktime */ '00000000'
        )
      );
    }
  }
