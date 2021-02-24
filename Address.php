<?PHP

  /**
   * BitWire - Bitcoin Address
   * Copyright (C) 2017 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  require_once ('BitWire/Transaction/Script.php'); // Needed for Base58
  
  class BitWire_Address {
    /* Type of this address */
    const TYPE_BITCOIN_P2PKH = 0x00;
    const TYPE_LITECOIN_P2PKH = 0x30;
    const TYPE_PEERCOIN_P2PKH = 0x37;
    
    private $addressType = 0x00;
    
    /* RIPEMD160/SHA256 for this address */
    private $Hash = '';
    
    // {{{ fromString
    /**
     * Create an address-object from a human readable address
     * 
     * @param string $Address
     * 
     * @access public
     * @return BitWire_Address
     **/
    public static function fromString ($Address) {
      // Try to decode the address
      if (strlen ($Address = BitWire_Transaction_Script::base58Decode ($Address)) != 25) {
        trigger_error ('Invalid address - input size mismatch');
        
        return;
      }
      
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
     * @param enum $Type
     * @param string $Hash
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($Type, $Hash) {
      $this->addressType = $Type;
      $this->Hash = $Hash;
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
      $addressData = $this->Hash;
      $addressType = $this->addressType;
      
      do {
        $addressData = chr ($addressType & 0x80) . $addressData;
        $addressType >>= 8;
      } while ($addressType > 0);
      
      $addressChecksum = hash ('sha256', hash ('sha256', $addressData, true), true);
      
      return BitWire_Transaction_Script::base58Encode ($addressData . substr ($addressChecksum, 0, 4));
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
        'hash' => bin2hex ($this->Hash),
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
    public function getType () {
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
    public function setType ($Type) {
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
    public function getHash () {
      return $this->Hash;
    }
    // }}}
    
    // {{{ getPublicKeyScript
    /**
     * Retrive script to pay to public-key-address
     * 
     * @access public
     * @return BitWire_Transaction_Script
     **/
    public function getPublicKeyScript () : BitWire_Transaction_Script {
      return new BitWire_Transaction_Script (
        chr (BitWire_Transaction_Script::OP_DUP) .
        chr (BitWire_Transaction_Script::OP_HASH160) .
        chr (strlen ($this->Hash)) .
        $this->Hash .
        chr (BitWire_Transaction_Script::OP_EQUALVERIFY) .
        chr (BitWire_Transaction_Script::OP_CHECKSIG)
      );
    }
    // }}}
  }

?>