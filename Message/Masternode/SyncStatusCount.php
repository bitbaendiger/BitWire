<?PHP

  /**
   * BitWire - Masternode Sync-Status-Count Message
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
  
  require_once ('BitWire/Message/Payload.php');
  
  class BitWire_Message_Masternode_SyncStatusCount extends BitWire_Message_Payload {
    const PAYLOAD_COMMAND = 'ssc';
    
    /* Item synced */
    const ITEM_LIST = 2;
    const ITEM_MNW = 3;
    const ITEM_BUDGET_PROP = 10;
    const ITEM_BUDGET_FIN = 11;
    
    private $Item = 0x00;
    
    /* Count */
    private $Count = 0;
    
    // {{{ parse
    /**
     * Parse data for this payload
     * 
     * @param string $Data
     * 
     * @access public
     * @return bool
     **/
    public function parse ($Data) {
      // Try to read all values
      $Length = strlen ($Data);
      $Offset = 0;
      
      if ((($Item = self::readUInt32 ($Data, $Offset, $Length)) === null) ||
          (($Count = self::readUInt32 ($data, $Offset, $Length)) === null))
        return false;
      
      // Commit to this instance
      $this->Item = $Item;
      $this->Count = $Count;
      
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
      return
        self::writeUInt32 ($this->Item) .
        self::writeUInt32 ($this->Count);
    }
    // }}}
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('ssc', 'BitWire_Message_Masternode_SyncStatusCount');

?>