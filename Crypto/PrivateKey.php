<?PHP

  /**
   * BitWire - ECDSA Private Key
   * Copyright (C) 2019 Bernd Holzmueller <bernd@quarxconnect.de>
   * 
   * This program is free software: you can redistribute it and/or modify
   * it under the terms of the GNU General Public License as published by
   * the Free Software Foundation, either version 3 of the License, or
   * (at your option) any later version.
   * 
   * This program is distributed in the hope that it will be useful,
   * but WITHOUT ANY WARRANTY; without even the implied warranty of
   * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   * GNU General Public License for more details.
   * 
   * You should have received a copy of the GNU General Public License
   * along with this program.  If not, see <http://www.gnu.org/licenses/>.
   **/
  
  require_once ('BitWire/Crypto/Curve.php');
  require_once ('BitWire/Crypto/PublicKey.php');
  require_once ('BitWire/Transaction/Script.php');
  
  class BitWire_Crypto_PrivateKey extends BitWire_Crypto_PublicKey {
    /* Version-Byte (merely for export) */
    private $Version = 0x00;
    
    /* Private part of this key */
    private $Key = null;
    
    // {{{ fromString
    /**
     * Import a private key from a base58-encoded string
     * 
     * @param string $String
     * @param BitWire_Crypto_Curve $Curve
     * 
     * @access public
     * @return BitWire_Crypto_PrivateKey
     **/
    public static function fromString ($String, BitWire_Crypto_Curve $Curve = null) : ?BitWire_Crypto_PrivateKey {
      // Remove Base58-Envelope
      $String = BitWire_Transaction_Script::base58Decode ($String);
      
      if ((($Length = strlen ($String)) < 37) || ($Length > 38))
        return null;
      
      // Make sure we have a curve
      if (!$Curve)
        $Curve = BitWire_Crypto_Curve_secp256k1::singleton ();
      
      // Extract informations from the key
      $Version = ord ($String [0]);
      $Key = gmp_import (substr ($String, 1, 32));
      $Compressed = (($Length == 38) && (ord ($String [33]) == 0x01));
      
      // Create the result
      $Instance = new static ();
      $Instance->Version = $Version;
      $Instance->Key = $Key;
      $Instance->Compresses = $Compressed;
      $Instance->Point = $Curve->G->mul ($Key);
      
      return $Instance;
    }
    // }}}
    
    // {{{ signCompact
    /**
     * Create a compact signature for a given message
     * 
     * @param string $Message
     * 
     * @access public
     * @return string
     **/
    public function signCompact ($Message) {
      // Create hash of the message
      $Digest = hash ('sha256', hash ('sha256', $Message, true), true);
      
      // Generate Nonce
      if (($Nonce = $this->getNonce ($Digest)) === null)
         return false;
      
      // Sign with nonce
      $P = $this->Point->Curve->G->mul ($Nonce);
      $r = $P->x % $this->Point->Curve->G->getOrder ();
      
      if ($r == 0)
        return false;
      
      $edr = gmp_import ($Digest) + ($this->Key * $r);
      $invk = gmp_invert ($Nonce, $this->Point->Curve->G->getOrder ());
      $kedr = $invk * $edr;
      
      $s = $kedr % $this->Point->Curve->G->getOrder ();
      
      // Create recoverable signature
      $Overflow = ($r > $this->Point->Curve->G->getOrder () ? 2 : 0);
      $Compressed = ($this->Compressed ? 4 : 0);
      $Odd = ($P->y % 2 == 1 ? 1 : 0);
      
      return
        chr (27 + $Odd + $Overflow + $Compressed) .
        str_pad (gmp_export ($r), 32, chr (0), STR_PAD_LEFT) .
        str_pad (gmp_export ($s), 32, chr (0), STR_PAD_LEFT);
    }
    // }}}
    
    // {{{ getNonce
    /**
     * Generate a nonce according to RFC6979
     * 
     * @param string $Digest
     * @param int $Size (optional)
     * 
     * @access private
     * @return GMP
     **/
    private function getNonce ($Digest, $Size = 32) : ?GMP {
      // Have our key as octet-string available
      $K = gmp_export ($this->Key);
      
      // RFC6979 3.2.b.
      $v = str_repeat ("\x01", 32);
      
      // RFC6979 3.2.c.
      $k = str_repeat ("\x00", 32);
      
      // RFC6979 3.2. d-g
      for ($i = 0; $i < 2; $i++) {
        $k = hash_hmac ('sha256', $v . chr ($i) . $K . $Digest, $k, true);
        $v = hash_hmac ('sha256', $v, $k, true);
      }
      
      // RFC6979 3.2.h.
      $Counter = 0;
      $Result = '';
      $Zero = gmp_init (0);
      
      while ($Counter < 1000) {
        if ($Counter > 0) {
          $k = hash_hmac ('sha256', $v . "\x00", $k, true);
          $v = hash_hmac ('sha256', $v, $k, true);
        }
        
        $Result = '';
        
        while (strlen ($Result) < $Size) {
          $v = hash_hmac ('sha256', $v, $k, true);
          $Result .= $v;
        }
        
        // Generate big number from the result
        $K = gmp_import (substr ($Result, 0, $Size));
        
        // Check if the result is valid
        if ((gmp_cmp ($K, $Zero) > 0) && (gmp_cmp ($K, $this->Point->Curve->p) < 0))
          return $K;
      }
      
      // Return error
      return null;
    }
    // }}}
  }

?>