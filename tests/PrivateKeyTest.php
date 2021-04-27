<?php

  declare (strict_types=1);

  use \PHPUnit\Framework\TestCase;
  use \BitBaendiger\BitWire;

  final class PrivateKeyTest extends TestCase {
    public function testCreateAndConvertKey () : BitWire\Crypto\PrivateKey {
      // Create a new private key
      $this->assertIsObject (
        $newKey = BitWire\Crypto\PrivateKey::newKey ()
      );
      
      // Export private key to a string
      $exportPrivateKey = $newKey->toString ();
      
      // Re-Import the key
      $importPrivateKey = BitWire\Crypto\PrivateKey::fromString ($exportPrivateKey);
      
      $this->assertEquals (
        $newKey->getID (),
        $importPrivateKey->getID ()
      );
      
      // Convert to public key and to binary
      $binaryPublicKey = $newKey->toPublicKey ()->toBinary ();
      
      // Re-Import the public key
      $this->assertIsObject (
        $newPublicKey = BitWire\Crypto\PublicKey::fromBinary ($binaryPublicKey)
      );
      
      $this->assertEquals (
        $newKey->getID (),
        $newPublicKey->getID ()
      );
      
      return $newKey;
    }
    
    /**
     * @depends testCreateAndConvertKey
     **/
    public function testCompactSignature (BitWire\Crypto\PrivateKey $privateKey) : void {
      $messageBinary = random_bytes (64);
      
      $compactSignature = $privateKey->signCompact ($messageBinary);
      
      $this->assertIsString ($compactSignature);
      $this->assertEquals (
        65,
        strlen ($compactSignature)
      );
      
      $publicKey = $privateKey->toPublicKey ();
      
      $this->assertTrue (
        $publicKey->verifyCompact ($messageBinary, $compactSignature)
      );
    }
  }