<?php

  /**
   * BitWire - Ping Message
   * Copyright (C) 2019-2021 Bernd Holzmueller <bernd@quarxconnect.de>
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

  namespace BitBaendiger\BitWire\Message;
  
  class Ping extends Payload {
    protected const PAYLOAD_COMMAND = 'ping';
    protected const PAYLOAD_MIN_VERSION = 60001;
    
    /* Number of pings sent/received */
    private static $Counter = 0;
    
    /* Nonce of this ping */
    private $Nonce = null;
    
    // {{{ __construct
    /**
     * Create a new ping-message-payload
     * 
     * @access friendly
     * @return void
     **/
    function __construct () {
      $this->Nonce = self::$Counter++;
    }
    // }}}
    
    // {{{ getNonce
    /**
     * Retrive the nonce of this ping
     * 
     * @access public
     * @return int
     **/
    public function getNonce () : int {
      return $this->Nonce;
    }
    // }}}
    
    // {{{ setNonce
    /**
     * Set a new nonce for this ping
     * 
     * @param int $Nonce
     * 
     * @access public
     * @return void
     **/
    public function setNonce (int $Nonce) {
      $this->Nonce = $Nonce;
    }
    // }}}
    
    // {{{ parse
    /**
     * Try to parse received payload for this message
     * 
     * @param string $Data
     * 
     * @access public
     * @return void
     **/
    public function parse ($Data) : void {
      // Don't allow payload on ping before protocol-version 60001
      if (is_object ($Message = $this->getMessage ()) && ($Message->getVersion () < self::PAYLOAD_MIN_VERSION)) {
        $this->Nonce = null;
        
        if (strlen ($Data) != 0)
          throw new \LengthException ('Ping must not contain data');
        
        return;
      }
      
      // Check the size of payload
      if (strlen ($Data) != 8)
        throw new \LengthException ('Invalid payload-size');
      
      // Unpack nonce from payload
      $Nonce = unpack ('Pnonce', $Data);
      $this->Nonce = array_shift ($Nonce);
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Convert this payload into binary
     * 
     * @access public
     * @return string
     **/
    public function toBinary () : string {
      // There is no payload before procotol-version 60001
      if (($this->Nonce === null) || (is_object ($Message = $this->getMessage ()) && ($Message->getVersion () < self::PAYLOAD_MIN_VERSION)))
        return '';
      
      // Return payload
      return pack ('P', $this->Nonce);
    }
    // }}}
  }
