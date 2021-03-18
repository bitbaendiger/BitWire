<?php

  /**
   * BitWire - Bitcoin Address
   * Copyright (C) 2017-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  namespace BitBaendiger\BitWire;
  
  class Address {
    /* Generic address-types */
    public const TYPE_PUBKEY = 0x00;
    public const TYPE_SCRIPT = 0x01;
    
    public const OUTPUT_NONSTANDARD = 0x00;
    public const OUTPUT_PUBKEY = 0x01; /* Script::isPublicKeyOutput() */
    public const OUTPUT_PUBKEYHASH = 0x02; /* Script::isPublicKeyHashOutput() */
    public const OUTPUT_SCRIPTHASH = 0x03; /* Script::isScriptHashOutput() */
    public const OUTPUT_MULTISIG = 0x04; /* Script::isMultiSignatureOutput() */
    public const OUTPUT_NULL_DATA = 0x05; /* Script::isNullDataOutput() */
    public const OUTPUT_WITNESS_V0_KEYHASH = 0x06; /* Script::isWitnessProgramOutput() */
    public const OUTPUT_WITNESS_V0_SCRIPTHASH = 0x07; /* Script::isWitnessProgramOutput() */
    public const OUTPUT_WITNESS_UNKNOWN = 0x08; /* Script::isWitnessProgramOutput() */
    
    /* Encoding-types */
    public const ENCODE_BASE58 = 0x00;
    public const ENCODE_BECH32 = 0x01;
    
    private $addressType = 0x00;
    
    /* Encoding-type for output */
    private $encodingType = Address::ENCODE_BASE58;
    
    /* RIPEMD160/SHA256 for this address */
    private $addressData = '';
    
    // {{{ fromString
    /**
     * Create an address-object from a human readable address
     * 
     * @param string $Address
     * 
     * @access public
     * @return Address
     **/
    public static function fromString (string $Address) : Address {
      // Try to decode the address
      if (strlen ($Address = Util\Base58::decode ($Address)) != 25)
        throw new \Exception ('Invalid address - input size mismatch');
      
      #// Validate the address
      #$Checksum = hash ('sha256', hash ('sha256', substr ($Address, 0, -4), true), true);
      #
      #if (strcmp (substr ($Checksum, 0, 4), substr ($Address, -4, 4)) != 0) {
      #  trigger_error ('Invalid address - checksum failure');
      #  
      #  return;
      #}
      
      // Create a new address
      return new static (ord ($Address [0]), substr ($Address, 1, -4));
    }
    // }}}
    
    // {{{ __construct
    /**
     * Create a new Bitcoin-Address-Object
     * 
     * @param enum $addressType
     * @param string $addressData
     * @param enum $encodingType (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (int $addressType, string $addressData, int $encodingType = Address::ENCODE_BASE58) {
      $this->addressType = $addressType;
      $this->addressData = $addressData;
      $this->encodingType = $encodingType;
    }
    // }}}
    
    // {{{ __toString
    /**
     * Create a human-readable string from this address
     * 
     * @access friendly
     * @return string
     **/
    function __toString () {
      if ($this->encodingType == $this::ENCODE_BECH32)
        return Util\Bech32::encode ('bc', $this->addressData, array ($this->addressType));
      
      $addressData = $this->Hash;
      $addressType = $this->addressType;
      
      do {
        $addressData = chr ($addressType & 0xFF) . $addressData;
        $addressType >>= 8;
      } while ($addressType > 0);
      
      $addressChecksum = hash ('sha256', hash ('sha256', $addressData, true), true);
      
      return Util\Base58::encode ($addressData . substr ($addressChecksum, 0, 4));
    }
    // }}}
    
    // {{{ __debugInfo
    /**
     * Prepare the object to be dumped via var_dump()
     * 
     * @access friendly
     * @return array
     **/
    function __debugInfo () {
      return [
        'address' => strval ($this),
        'type' => $this->addressType,
        'data' => bin2hex ($this->addressData),
        'encoding' => $this->encodingType,
      ];
    }
    // }}}
    
    // {{{ getType
    /**
     * Retrive the type of this address
     * 
     * @access public
     * @return enum
     **/
    public function getType () : int {
      return $this->addressType;
    }
    // }}}
    
    // {{{ setType
    /**
     * Override the type of this address
     * 
     * @param enum $Type
     * 
     * @access public
     * @return void
     **/
    public function setType (int $Type) : void {
      $this->addressType = (int)$Type;
    }
    // }}}
    
    // {{{ getHash
    /**
     * Retrive the RIPEMD160/SHA256-Hash for this address
     * 
     * @access public
     * @return string
     **/
    public function getHash () : string {
      return $this->addressData;
    }
    // }}}
    
    // {{{ getPublicKeyScript
    /**
     * Retrive script to pay to public-key-address
     * 
     * @access public
     * @return Transaction\Script
     **/
    public function getPublicKeyScript () : Transaction\Script {
      return new Transaction\Script (
        chr (Transaction\Script::OP_DUP) .
        chr (Transaction\Script::OP_HASH160) .
        chr (strlen ($this->addressData)) .
        $this->addressData .
        chr (Transaction\Script::OP_EQUALVERIFY) .
        chr (Transaction\Script::OP_CHECKSIG)
      );
    }
    // }}}
  }
