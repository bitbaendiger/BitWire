<?php

  /**
   * BitWire - ECDSA Public Key
   * Copyright (C) 2020-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  declare (strict_types=1);

  namespace BitBaendiger\BitWire\Crypto;
  use \BitBaendiger\BitWire;
  
  class PublicKey implements BitWire\ABI\Hashable {
    private const COMPACT_SIGNATURE_SIZE = 65;
    
    /* Curve-Point of this public key */
    protected $curvePoint = null;
    
    /* Prefer compressed output */
    protected $isCompressed = true;
    
    // {{{ fromBinary
    /**
     * Restore a public key from binary
     * 
     * @param string $Binary
     * @param Curve $Curve
     * 
     * @access public
     * @return PublicKey
     **/
    public static function fromBinary (string $Binary, Curve $Curve = null) : PublicKey {
      // Get the length of the key
      if (($Length = strlen ($Binary)) < 1)
        throw new \LengthException ('Input too short');
      
      // Make sure we have a curve
      if (!$Curve)
        $Curve = Curve\Secp256k1::singleton ();
      
      // Get the format of the key
      $Type = ord ($Binary [0]);
      
      // Create the result-key
      $Result = new static ();
      
      $Result->curvePoint = Curve\Point::fromPublicKey ($Curve, $Binary);
      $Result->isCompressed = (($Type == 0x02) || ($Type == 0x03));
      
      return $Result;
    }
    // }}}
    
    // {{{ recoverCompact
    /**
     * Recover a public key from message-digest and a signature
     * 
     * @param string $Hash
     * @param string $Signature
     * @param Curve $Curve (optional)
     * 
     * @access public
     * @return PublicKey
     **/
    public static function recoverCompact (string $Hash, string $Signature, Curve $Curve = null) : PublicKey {
      // Check size of signature
      if (($sigSize = strlen ($Signature)) != self::COMPACT_SIGNATURE_SIZE)
        throw new \LengthException ('Invalid size');
      
      // Make sure we have a curve
      if (!$Curve)
        $Curve = Curve\Secp256k1::singleton ();
      
      // Read signature-flags
      $Flags = ord ($Signature [0]);
      
      if (($Flags < 27) || ($Flags > 34))
        throw new \Exception ('Invalid flags');
      
      $recID = ($Flags - 27) & 0x03;
      $Compressed = ((($Flags - 27) & 0x04) != 0);
      
      // {{{ secp256k1_ecdsa_recoverable_signature_parse_compact
      $Order = $Curve->G->getOrder ();
      $OrderLen = strlen (gmp_export ($Order));
      
      if ($sigSize != $OrderLen * 2 + 1)
        throw new \LengthException ('Invalid signature-size');
      
      $r = gmp_import (substr ($Signature, 1, $OrderLen));
      $s = gmp_import (substr ($Signature, $OrderLen + 1, $OrderLen));
      // }}}
      
      // {{{ secp256k1_ecdsa_recover
      $e = gmp_import ($Hash);
      $x = gmp_add ($r, gmp_mul (gmp_init ((int)floor ($recID / 2)), $Order));
      
      $alpha = gmp_mod (gmp_add (gmp_add (gmp_pow ($x, 3), gmp_mul ($Curve->a, $x)), $Curve->b), $Curve->p);
      $beta = gmp_powm ($alpha, gmp_div_q (gmp_add ($Curve->p, gmp_init (1)), gmp_init (4)), $Curve->p);
      
      if (gmp_cmp (gmp_mod (gmp_sub ($beta, gmp_init ($recID)), gmp_init (2)), gmp_init (0)) == 0)
        $y = $beta;
      else
        $y = gmp_sub ($Curve->p, $beta);
      
      $R = new Curve\Point ($Curve, $x, $y, $Order);
      $mE = gmp_mod (gmp_neg ($e), $Order);
      $invR = gmp_invert ($r, $Order);
      
      $Point = $R->mul ($s)->add ($Curve->G->mul ($mE), true)->mul ($invR);
      // }}}
      
      $PublicKey = new static ();
      $PublicKey->curvePoint = $Point;
      $PublicKey->isCompressed = $Compressed;
      
      return $PublicKey;
    }
    // }}}
    
    // {{{ getID
    /**
     * Retrive the unique ID of this public key
     * 
     * @param bool $Compressed (optional)
     * 
     * @access public
     * @return string
     **/
    public function getID (bool $Compressed = null) : string {
      // Convert this public key into binary
      $Binary = $this->toBinary ($Compressed);
      
      // Hash the key
      return bin2hex (strrev (hash ('ripemd160', hash ('sha256', $Binary, true), true)));
    }
    // }}}
    
    // {{{ getAddress
    /**
     * Retrive the address for this public key
     * 
     * @param bool $Compressed (optional)
     * @param int $addressType (optional)
     * 
     * @access public
     * @return BitWire\Address
     **/
    public function getAddress (bool $Compressed = null, int $addressType = 0x00) : BitWire\Address {
      return new BitWire\Address ($addressType, hash ('ripemd160', hash ('sha256', $this->toBinary ($Compressed), true), true));
    }
    // }}}
    
    // {{{ getCurve
    /**
     * Retrive the curve for this public key
     * 
     * @access public
     * @return Curve
     **/
    public function getCurve () : Curve {
      return $this->curvePoint->Curve;
    }
    // }}}
    
    // {{{ getHash
    /**
     * Create a hash for this public key
     * 
     * @access public
     * @return BitWire\Hash
     **/
    public function getHash () : BitWire\Hash {
      return new BitWire\Hash ($this->toBinary (), false);
    }
    // }}}
    
    // {{{ isCompressed
    /**
     * Check if this public key is compressed
     * 
     * @access public
     * @return bool
     **/
    public function isCompressed () : bool {
      return $this->isCompressed;
    }
    // }}}
    
    // {{{ verifyCompact
    /**
     * Verify the signature for a given message
     * 
     * @param string $Message
     * @param string $Signature
     * 
     * @access public
     * @return bool
     **/
    public function verifyCompact (string $Message, string $Signature) : bool {
      // Create hash of the message
      $Digest = hash ('sha256', hash ('sha256', $Message, true), true);
      
      // Recover public key from message
      if (!is_object ($PublicKey = self::recoverCompact ($Digest, $Signature)))
        return false;
      
      return ($this->getID () === $PublicKey->getID ());
    }
    // }}}
    
    // {{{ toPublicKey
    /**
     * Make sure this is only a public key
     * 
     * @access public
     * @return PublicKey
     **/
    public function toPublicKey () : PublicKey {
      // Check if this instance isn't a derivation
      if (strcasecmp (get_class ($this), __CLASS__) == 0)
        return $this;
      
      // Create a copy of our public part
      $Result = new PublicKey ();
      $Result->curvePoint = clone $this->curvePoint;
      $Result->isCompressed = $this->isCompressed;
      
      return $Result;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Convert this to binary
     * 
     * @access public
     * @return string
     **/
    public function toBinary ($Compressed = null) : string {
      if ($Compressed === null)
        $Compressed = $this->isCompressed;
      
      return $this->curvePoint->toPublicKey ($Compressed);
    }
    // }}}
  }
