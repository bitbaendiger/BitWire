<?PHP

  /**
   * BitWire - ECDSA Private Key
   * Copyright (C) 2020 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  // Make sure GMP is available
  if (!extension_loaded ('gmp') && (!function_exists ('dl') || !dl ('gmp.so'))) {
    trigger_error ('Missing required GMP-Extension');
  
    return;
  }
  
  require_once ('BitWire/Crypto/Curve.php');
  require_once ('BitWire/Crypto/PublicKey.php');
  require_once ('BitWire/Transaction/Script.php');
  
  class BitWire_Crypto_PrivateKey extends BitWire_Crypto_PublicKey {
    /* Version-Byte (merely for export) */
    private $keyVersion = 0x00;
    
    /* Private part of this key */
    private $gmpKey = null;
    
    // {{{ fromString
    /**
     * Import a private key from a base58-encoded string
     * 
     * @param string $String
     * @param BitWire_Crypto_Curve $onCurve
     * 
     * @access public
     * @return BitWire_Crypto_PrivateKey
     **/
    public static function fromString ($String, BitWire_Crypto_Curve $onCurve = null) : ?BitWire_Crypto_PrivateKey {
      // Remove Base58-Envelope
      $String = BitWire_Transaction_Script::base58Decode ($String);
      
      if ((($Length = strlen ($String)) < 37) || ($Length > 38))
        return null;
      
      // Make sure we have a curve
      if (!$onCurve)
        $onCurve = BitWire_Crypto_Curve_secp256k1::singleton ();
      
      // Extract informations from the key
      $keyVersion = ord ($String [0]);
      $Key = gmp_import (substr ($String, 1, 32));
      $Compressed = (($Length == 38) && (ord ($String [33]) == 0x01));
      
      // Create the result
      $Instance = new static ();
      $Instance->keyVersion = $keyVersion;
      $Instance->gmpKey = $Key;
      $Instance->isCompressed = $Compressed;
      $Instance->curvePoint = $onCurve->G->mul ($Key);
      
      return $Instance;
    }
    // }}}
    
    // {{{ fromBinaryNumber
    /**
     * Restore a private key from binary data
     * 
     * @param string $binaryData
     * @param bool $isCompressed
     * @param BitWire_Crypto_Curve $onCurve (optional)
     * 
     * @access public
     * @return BitWire_Crypto_PrivateKey
     **/
    public static function fromBinaryNumber ($binaryData, $isCompressed, BitWire_Crypto_Curve $onCurve = null) : ?BitWire_Crypto_PrivateKey {
      // Check size of the key
      if (strlen ($binaryData) != 32)
        return null;
      
      // Make sure we have a curve
      if (!$onCurve)
        $onCurve = BitWire_Crypto_Curve_secp256k1::singleton ();
      
      // Create private key
      $Instance = new static ();
      $Instance->gmpKey = gmp_import ($binaryData);
      $Instance->isCompressed = $isCompressed;
      $Instance->curvePoint = $onCurve->G->mul ($Instance->gmpKey);
      
      return $Instance;
    }
    // }}}
    
    // {{{ fromDER
    /**
     * Read a private Key from DER-encoded data
     * 
     * @param string $Data
     * @param BitWire_Crypto_Curve $onCurve (optional)
     * 
     * @access public
     * @return BitWire_Crypto_PrivateKey
     **/
    public static function fromDER ($Data, BitWire_Crypto_Curve $onCurve = null) : ?BitWire_Crypto_PrivateKey {
      // Read the whole sequence from DER
      $Offset = $Type = 0;
      $Sequence = self::asn1read ($Data, $Offset, $Type);
      
      if ($Type != 0x30)
        return null;
      
      // Check version
      $Offset = 0;
      
      if ((($keyVersion = self::asn1read ($Sequence, $Offset, $Type)) === null) ||
          ($Type != 0x02) ||
          (strcmp ($keyVersion, "\x01") != 0))
        return null;
      
      // Extract the key
      if ((($Key = self::asn1read ($Sequence, $Offset, $Type)) === null) ||
          ($Type != 0x04))
        return null;
      
      // Check for additional data
      $Compressed = true;
      
      if (($Data = self::asn1read ($Sequence, $Offset, $Type)) !== null) {
        // Check for EC-Parameters
        if ($Type == 0xA0) {
          $cOffset = 0;
          $cSequence = self::asn1read ($Data, $cOffset, $Type);
          
          $cOffset = 0;
          $cVersion = self::asn1read ($cSequence, $cOffset, $Type);
          
          $cCurve = self::asn1read ($cSequence, $cOffset, $Type);
          $oCurve = 0;
          $CurveID = self::asn1read ($cCurve, $oCurve, $Type);
          $CurveP = self::asn1read ($cCurve, $oCurve, $Type);
          
          $cCurveP = self::asn1read ($cSequence, $cOffset, $Type);
          $oCurve = 0;
          $CurveA = self::asn1read ($cCurveP, $oCurve, $Type);
          $CurveB = self::asn1read ($cCurveP, $oCurve, $Type);
          
          $CurveG = self::asn1read ($cSequence, $cOffset, $Type);
          $CurveN = self::asn1read ($cSequence, $cOffset, $Type);
          $CurveM = self::asn1read ($cSequence, $cOffset, $Type);
          
          $nCurve = new BitWire_Crypto_Curve (gmp_import ($CurveP), gmp_import ($CurveA), gmp_import ($CurveB));
          $nCurve->m = gmp_import ($CurveM);
          $nCurve->n = gmp_import ($CurveN);
          $nCurve->G = BitWire_Crypto_Curve_Point::fromPublicKey ($nCurve, $CurveG, $onCurve->n);
          
          if ($onCurve) {
            if (($onCurve->p <> $nCurve->p) ||
                ($onCurve->a <> $nCurve->a) ||
                ($onCurve->b <> $nCurve->b) ||
                ($onCurve->m <> $nCurve->m) ||
                ($onCurve->n <> $nCurve->n))
              trigger_error ('Specified curve does not match', E_USER_WARNING);
          } else
            $onCurve = $nCurve;
          
          // Check if there is a public key as well
          $Data = self::asn1read ($Sequence, $Offset, $Type);
        }
        
        // Check for public key
        if (($Data !== null) && ($Type == 0xA1))
          // TODO?
          $Compressed = (strlen ($Data) < 37);
      }
      
      if (!$onCurve) {
        trigger_error ('Missing curve for import');
        
        return null;
      }
      
      // Extract informations from the key
      $keyVersion = 1;
      $Key = gmp_import ($Key);
      $Compressed = $Compressed;
      
      // Create the result
      $Instance = new static ();
      $Instance->keyVersion = $keyVersion;
      $Instance->gmpKey = $Key;
      $Instance->isCompressed = $Compressed;
      $Instance->curvePoint = $onCurve->G->mul ($Key);
      
      return $Instance;
    }
    // }}}
    
    // {{{ asn1read
    /**
     * Read an ASN.1-Bucket from a string
     * 
     * @param string $Data
     * @param int $Offset
     * @param int $Type (optional)
     * @param int $Length (optional)
     * 
     * @access private
     * @return string
     **/
    private static function asn1read (&$Data, &$Offset, &$Type = null, $Length = null) {
      // Make sure we know the length of our input
      if ($Length === null)
        $Length = strlen ($Data);

      // Make sure we have enough data to read
      if ($Length - $Offset < 2)
        return null;

      $pOffset = $Offset;
      $pType = ord ($Data [$pOffset++]);
      $pLength = ord ($Data [$pOffset++]);

      if ($pLength > 0x80) {
        // Check if there are enough bytes to read the extended length
        if ($Length - $pOffset < $pLength - 0x80)
          return null;

        // Read the extended length
        $b = $pLength - 0x80;
        $pLength = 0;
        
        for ($i = 0; $i < $b; $i++)
          $pLength = ($pLength << 8) | ord ($Data [$pOffset++]);
      }

      // Make sure there are enough bytes to read the payload
      if ($Length - $pOffset < $pLength)
        return null;

      // Read all data and move the offset
      $Type = $pType;
      $Offset = $pOffset + $pLength;
      
      return substr ($Data, $pOffset, $pLength);
    }
    // }}}
    
    // {{{ newKey
    /**
     * Create a new private key
     * 
     * @param BitWire_Crypto_Curve $onCurve (optional)
     * @param bool $Compressed (optional)
     * @param int $keyVersion (optional)
     * 
     * @access public
     * @return BitWire_Crypto_PrivateKey
     **/
    public static function newKey (BitWire_Crypto_Curve $onCurve = null, $Compressed = true, $keyVersion = null) : BitWire_Crypto_PrivateKey {
      // Make sure we have a curve
      if (!$onCurve)
        $onCurve = BitWire_Crypto_Curve_secp256k1::singleton ();
      
      // Create a new key
      $Instance = new static ();
      
      if ($keyVersion !== null)
        $Instance->keyVersion = (int)$keyVersion;
      
      $Instance->gmpKey = gmp_random_range ($onCurve->m, $onCurve->n);
      $Instance->isCompressed = $Compressed;
      $Instance->curvePoint = $onCurve->G->mul ($Instance->gmpKey);
      
      // Return the key
      return $Instance;
    }
    // }}}
    
    // {{{ getVersion
    /**
     * Retrive the version of this private key
     * 
     * @access public
     * @return int
     **/
    public function getVersion () {
      return $this->keyVersion;
    }
    // }}}
    
    // {{{ setVersion
    /**
     * Set the version of this private key
     * 
     * @param int $keyVersion
     * 
     * @access public
     * @return void
     **/
    public function setVersion ($keyVersion) {
      $this->keyVersion = (int)$keyVersion;
    }
    // }}}
    
    // {{{ signCompact
    /**
     * Create a compact signature for a given message
     * 
     * @param string $Message
     * @param bool $Compressed (optional)
     * 
     * @access public
     * @return string
     **/
    public function signCompact ($Message, $Compressed = null) {
      // Create hash of the message
      $Digest = hash ('sha256', hash ('sha256', $Message, true), true);
      
      // Generate Nonce
      if (($Nonce = $this->getNonce ($Digest)) === null)
         return false;
      
      // Sign with nonce
      $G = $this->curvePoint->Curve->G;
      $P = $G->mul ($Nonce);
      $r = $P->x % $G->getOrder ();
      
      if ($r == 0)
        return false;
      
      $edr = gmp_import ($Digest) + ($this->gmpKey * $r);
      $invk = gmp_invert ($Nonce, $G->getOrder ());
      $kedr = $invk * $edr;
      
      $s = $kedr % $G->getOrder ();
      
      // Create recoverable signature
      $Overflow = ($r > $G->getOrder () ? 2 : 0);
      $Compressed = (($Compressed === true) || (($Compressed === null) && $this->isCompressed) ? 4 : 0);
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
      $K = gmp_export ($this->gmpKey);
      
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
        if ((gmp_cmp ($K, $Zero) > 0) && (gmp_cmp ($K, $this->curvePoint->Curve->p) < 0))
          return $K;
      }
      
      // Return error
      return null;
    }
    // }}}
    
    // {{{ toString
    /**
     * Export the private key to a string
     * 
     * @access public
     * @return string
     **/
    public function toString () {
      $Binary =
        chr ($this->keyVersion) .
        str_pad (gmp_export ($this->gmpKey), 32, "\x00", STR_PAD_LEFT) .
        ($this->isCompressed ? "\x01" : '');
      
      return BitWire_Transaction_Script::base58Encode (
        $Binary .
        substr (hash ('sha256', hash ('sha256', $Binary, true), true), 0, 4)
      );
    }
    // }}}
  }

?>