<?php

  /**
   * BitWire - Bitcoin Address Message Payload
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

  namespace BitBaendiger\BitWire\Message;
  use \BitBaendiger\BitWire;
  use \quarxConnect\Events;
  
  class Addresses extends Payload {
    protected const PAYLOAD_COMMAND = 'addr';
    
    /* Known addresses */
    private $Addresses = [ ];
    
    // {{{ getAddresses
    /**
     * Retrive all addresses stored here
     * 
     * @access public
     * @return array
     **/
    public function getAddresses () : array {
      return $this->Addresses;
    }
    // }}}
    
    // {{{ parse
    /** 
     * Parse binary contents for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return void
     **/
    public function parse ($Data) : void {
      // Try to read the version
      $Version = (is_object ($Message = $this->getMessage ()) ? $Message->getVersion () : null);
      
      // Read the number of entries#
      $Length = strlen ($Data);
      $Offset = 0;
      
      $Count = $this::readCompactSize ($Data, $Offset, $Length);
      
      // Check wheter to auto-detect the version
      if ($Version === null) {
        if ((($Length - $Offset) % 30) == 0)
          $Version = 70015;
        else
          $Version = 31401;
      }
      
      // Sanatize length of data
      if ((($Length - $Offset) % ($Version >= 31402 ? 30 : 26)) != 0)
        throw new \LengthException ('Invalid length');
      
      // Read addresses
      $this->Addresses = [ ];
      
      for ($i = 0; $i < $Count; $i++) {
        // Try to unpack the data
        if ($Version >= 31402)
          $Values = unpack ('Vtimestamp/Pservices/a16address/nport', substr ($Data, $Offset + $i * 30, 30));
        else
          $Values = unpack ('Pservices/a16address/nport', substr ($Data, $Offset + $i * 26, 26));
        
        if (!$Values)
          throw new \ValueError ('Failed to unpack address');
        
        // Push to addresses
        $this->Addresses [] = new BitWire\Controller\Address (
          Events\Socket::ip6fromBinary ($Values ['address']),
          $Values ['port'],
          $Values ['services'],
          (isset ($Values ['timestamp']) ? $Values ['timestamp'] : null)
        );
      }
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
      // Check the version we are running with
      $Version = (is_object ($Message = $this->getMessage ()) ? $Message->getVersion () : 700015);
      
      // Output number of entries
      $Buffer = $this::toCompactSize (count ($this->Addresses));
      
      // Output each entry
      foreach ($this->Addresses as $Address)
        if ($Version >= 31402)
          $Buffer .= pack ('VPa16n', $Address->getTimestamp (), $Address->getServices (), qcEvents_Socket::ip6toBinary ($Address->getIPAddress ()), $Address->getPort ());
        else
          $Buffer .= pack ('Pa16n', $Address->getServices (), qcEvents_Socket::ip6toBinary ($Address->getIPAddress ()), $Address->getPort ());
      
      // Return the result
      return $Buffer;
    }
    // }}}
  }
