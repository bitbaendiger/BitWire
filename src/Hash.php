<?PHP

  namespace BitBaendiger\BitWire;
  
  /**
   * BitWire - Hash
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
  
  class Hash {
    /* Internally stored hash */
    private $Data = '';
    
    // {{{ fromBinary
    /**
     * Create a hash from its binary representation
     * 
     * @param string $Data
     * @param bool $networkByteOrder (optional)
     * 
     * @access public
     * @return Hash
     **/
    public static function fromBinary ($Data, $networkByteOrder = false) : Hash {
      $Instance = new static;
      $Instance->Data = ($networkByteOrder ? strrev ($Data) : $Data);
      
      return $Instance;
    }
    // }}}
    
    // {{{ fromHex
    /**
     * Create a hash from its hex represenation
     * 
     * @param string $Data
     * @param bool $networkByteOrder (optional)
     * 
     * @access public
     * @return Hash
     **/
    public static function fromHex ($Data, $networkByteOrder = false) : Hash {
      return static::fromBinary (hex2bin ($Data), $networkByteOrder);
    }
    // }}}
    
    // {{{ __construct
    /**
     * Create a new Hash
     * 
     * @param string $Data (optional) Generate hash from this data
     * @param bool $networkByteOrder (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct ($Data = null, $networkByteOrder = true) {
      if ($Data === null)
        return;
      
      $this->Data = hash ('sha256', hash ('sha256', $Data, true), true);
      
      if ($networkByteOrder)
        $this->Data = strrev ($this->Data);
    }
    // }}}
    
    // {{{ __toString
    /**
     * Output this hash in human readable form
     * 
     * @access friendly
     * @return string
     **/
    function __toString () {
      return bin2hex ($this->Data);
    }
    // }}}
    
    // {{{ __debugInfo
    /**
     * Prepare output for vardump()
     * 
     * @access public
     * @return array
     **/
    function __debugInfo () {
      return array (
        'hash' => $this->__toString (),
      );
    }
    // }}}
    
    // {{{ isEmpty
    /**
     * Check for an empty hash
     * 
     * @access public
     * @return bool
     **/
    public function isEmpty () {
      return (strcmp ($this->Data, "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00") == 0);
    }
    // }}}
    
    // {{{ toBinary
    /** 
     * Convert this hash to binary representation
     * 
     * @access public
     * @return string
     **/
    public function toBinary ($networkByteOrder = false) {
      return ($networkByteOrder ? strrev ($this->Data) : $this->Data);
    }
    // }}}
    
    // {{{ compare
    /**
     * Compare this hash with another one
     * 
     * @param BitWire_Hash $With
     * 
     * @access public
     * @return bool
     **/
    public function compare (BitWire_Hash $With) {
      return (strcmp ($this->Data, $With->Data) == 0);
    }
    // }}}
  }

?>