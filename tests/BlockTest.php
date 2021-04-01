<?php

  declare (strict_types=1);

  use PHPUnit\Framework\TestCase;

  final class BlockTest extends TestCase {
    public function testZerocoinBlock () {
      # {
      #   "hash": "41f514d70d4501ecaa5a1798b3e9dba794d51e224beed6c104e81739a2ed9d44",
      #   "height": 1290778,
      #   "version": 5,
      #   "merkleroot": "ea72bfe704e569ead3250c603bd5c4a72ea91b452c022f34e2369fa2bb4b11cd",
      #   "acc_checkpoint": "60fc095fedbb32e1072459107c5bc3ff65d3352f2df1152b861e0e5e6178964d",
      #   "tx": [
      #     "3ca40268478eb2b0f5e6f1bcdeee5a85cbc72d3d9e13faa12cf39fc4f54a4dd1",
      #     "d499fc6dec2ea667b07efa91bcad172d5d364f0c1f75413db03102944a5b3650"
      #   ],
      #   "time": 1617316039,
      #   "nonce": 0,
      #   "bits": "1a0e5c53",
      #   "previousblockhash": "96815e3d626a6eabc83aa46c6f9c5df52748efa01e0b4de0552ff85b0ab57fe8",
      # }

      $blockHex = hex2bin (
        // Version
        '05000000' .
        
        // Previous block
        'e87fb50a5bf82f55e04d0b1ea0ef4827f55d9c6f6ca43ac8ab6e6a623d5e8196' .
        
        // Merkle root
        'cd114bbba29f36e2342f022c451ba92ea7c4d53b600c25d3ea69e504e7bf72ea' .
        
        // Time
        'c7486660' .
        
        // Bits
        '535c0e1a' .
        
        // Nonce
        '00000000' .
        
        // Accumulator Checkout
        '4d9678615e0e1e862b15f12d2f35d365ffc35b7c10592407e132bbed5f09fc60' .
        
        // Number of transactions
        '02' .
        
        // tx[0] Coinbase
        '01000000010000000000000000000000000000000000000000000000000000000000000000ffffffff06031ab2130101ffffffff0100000000000000000000000000' .
        
        // tx[1] Staking
        '0100000001a2ca67cffddc5003f88a1f07bf9fb49e8d95787ea3fecd281f9566198de1189e0100000049483045022100d8e4b22a542850d1ea55a9725dc6a4441ae1c424beb5f0143a92ff597f9afc3f02205c8df74a11a63cb9c6e4d0e6f1870f230311059c625cd9cb898134c2863de71501ffffffff0400000000000000000080a85af23e0000002321020dfacd4edcafe5194846d351a09b983cc43e6c288350a7e4ee78661da5bb7beeacc0abba94080000001976a91459d3dded8e4ebf864d603eb4c5b2ec2db012184988ac80a4ec38010000001976a9142ba51726eab17f4039c465a001eb7f4b7f5191ac88ac00000000'
      );
      
      $block = new \BitBaendiger\BitWire\Block ();
      $block->parse ($blockHex);
      
      $this->assertEquals (
        '41f514d70d4501ecaa5a1798b3e9dba794d51e224beed6c104e81739a2ed9d44',
        (string)$block->getHash ()
      );
      
      $this->assertEquals (
        5,
        $block->getVersion ()
      );
      
      $this->assertCount (
        2,
        $block->getTransactions ()
      );
      
      $blockTransactions = $block->getTransactions ();
      
      $this->assertEquals (
        '3ca40268478eb2b0f5e6f1bcdeee5a85cbc72d3d9e13faa12cf39fc4f54a4dd1',
        (string)$blockTransactions [0]->getHash ()
      );
      
      $this->assertEquals (
        'd499fc6dec2ea667b07efa91bcad172d5d364f0c1f75413db03102944a5b3650',
        (string)$blockTransactions [1]->getHash ()
      );
      
      $this->assertEquals (
        $blockHex,
        $block->toBinary ()
      );
    }
  }
