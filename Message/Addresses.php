<?PHP

  /**
   * BitWire - Bitcoin Address Message Payload
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
  
  require_once ('qcEvents/Socket.php');
  require_once ('BitWire/Controller/Address.php');
  
  class BitWire_Message_Addresses extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'addr';
    
    /* Known addresses */
    private $Addresses = array ();
    
    // {{{ getAddresses
    /**
     * Retrive all addresses stored here
     * 
     * @access public
     * @return array
     **/
    public function getAddresses () {
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
     * @return bool
     **/
    public function parse ($Data) {
      // Try to read the version
      $Version = (is_object ($Message = $this->getMessage ()) ? $Message->getVersion () : null);
      
      // Read the number of entries#
      $Length = strlen ($Data);
      $Offset = 0;
      
      if (($Count = $this::readCompactSize ($Data, $Offset, $Length)) === null)
        return false;
      
      // Check wheter to auto-detect the version
      if ($Version === null) {
        if ((($Length - $Offset) % 30) == 0)
          $Version = 70015;
        else
          $Version = 31401;
      }
      
      // Sanatize length of data
      if ((($Length - $Offset) % ($Version >= 31402 ? 30 : 26)) != 0)
        return false;
      
      // Read addresses
      $this->Addresses = array ();
      
      for ($i = 0; $i < $Count; $i++) {
        // Try to unpack the data
        if ($Version >= 31402)
          $Values = unpack ('Vtimestamp/Pservices/a16address/nport', substr ($Data, $Offset + $i * 30, 30));
        else
          $Values = unpack ('Pservices/a16address/nport', substr ($Data, $Offset + $i * 26, 26));
        
        if (!$Values)
          return false;
        
        // Push to addresses
        $this->Addresses [] = new BitWire_Controller_Address (
          qcEvents_Socket::ip6fromBinary ($Values ['address']),
          $Values ['port'],
          $Values ['services'],
          (isset ($Values ['timestamp']) ? $Values ['timestamp'] : null)
        );
      }
      
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
      // Check the version we are running with
      $Version = (is_object ($Message = $this->getMessage ()) ? $Message->getVersion () : 700015);
      
      // Output number of entries
      $Buffer = $this::toCompactSize (count ($this->Addresses));
      
      // Output each entry
      foreach ($this->Addresses as $Address)
        if ($Version >= 31402)
          $Buffer .= pack ('VPa16n', $Address ['timestamp'], $Address ['services'], qcEvents_Socket::ip6toBinary ($Address ['address']), $Address ['port']);
        else
          $Buffer .= pack ('Pa16n', $Address ['services'], qcEvents_Socket::ip6toBinary ($Address ['address']), $Address ['port']);
      
      // Return the result
      return $Buffer;
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('addr', 'BitWire_Message_Addresses');

?>