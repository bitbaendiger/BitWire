<?PHP

  /**
   * BitWire - Block Message
   * Copyright (C) 2017-2020 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  require_once ('BitWire/Block.php');
  require_once ('BitWire/Message/Payload/Hashable.php');
  
  class BitWire_Message_Block extends BitWire_Message_Payload_Hashable {
    const PAYLOAD_COMMAND = 'block';
    
    /* Block stored on this message */
    private $Block = null;
    
    // {{{ __construct
    /**
     * Create a new block-message
     * 
     * @access friendly
     * @return void
     **/
    function __construct () {
      
    }
    // }}}
    
    // {{{ __debugInfo
    /**
     * Prepare output for var_dump() of this object
     * 
     * @access friendly
     * @return array
     **/
    function __debugInfo () {
      return array (
        'block' => $this->Block,
      );
    }
    // }}}
    
    // {{{ getHash
    /**
     * Retrive the hash for this payload
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public function getHash () : BitWire_Hash {
      if ($this->Block)
        return $this->Block->getHash ();

      return new BitWire_Hash;
    }
    // }}}
    
    // {{{ getBlock
    /**
     * Retrive the block stored on this message
     * 
     * @access public
     * @return BitWire_Block
     **/
    public function getBlock () : ?BitWire_Block {
      return $this->Block;
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse data for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parse ($Data) {
      $Block = new BitWire_Block;
      $Offset = 0;
      
      if (!$Block->parse ($Data, $Offset))
        return false;
      
      $this->Block = $Block;
      
      return true;
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Convert this payload into binary
     * 
     * @access public
     * @return string
     **/
    public function toBinary () {
      if ($this->Block)
        return $this->Block->toBinary ();
    }
    // }}}
  }
  
  // Register our payload-class
  BitWire_Message_Payload::registerCommand ('block', 'BitWire_Message_Block');

?>