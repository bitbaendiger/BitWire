<?php

  declare (strict_types=1);

  use \PHPUnit\Framework\TestCase;
  use \BitBaendiger\BitWire;

  final class PublicKeyTest extends TestCase {
    public function testImport () : void {
      $publicKeys = [
        '0293530c959faca23c51e0d0ca38b416c8eb8567ffa2715cae46a2bd30fc8dcc52',
        '032efa8e58ec3d1e78cda6d2e19e0ebc440de51311f60023c04f2aad341ee8c1fa',
        '0249973d859b2707666bb7f24c28bafff2bbc33c7caa0ae91b544971b48f838253',
        '030c44a9495155cc0bd5f9b6329fc0156ad437f51c84a70148fcbd9c594a99adc1',
      ];
      
      foreach ($publicKeys as $publicKeyHex) {
        $publicKey = BitWire\Crypto\PublicKey::fromHex ($publicKeyHex);
        
        $this->assertIsObject ($publicKey);
        
        $this->assertEquals (
          bin2hex ($publicKey->toBinary ()),
          $publicKeyHex
        );
      }
    }
  }
