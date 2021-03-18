<?php

  /**
   * BitWire - Masternode Sync-Status-Count Message
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

  namespace BitBaendiger\BitWire\Message\Masternode;
  use \BitBaendiger\BitWire\Message;
  use \BitBaendiger\BitWire;
  
  class SyncStatusCount extends Message\Payload {
    protected const PAYLOAD_COMMAND = 'ssc';
    
    /* Item synced */
    public const ITEM_LIST = 2;
    public const ITEM_MNW = 3;
    public const ITEM_BUDGET_PROP = 10;
    public const ITEM_BUDGET_FIN = 11;
    
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
     * @return void
     **/
    public function parse (string $Data) : void {
      // Try to read all values
      $Length = strlen ($Data);
      $Offset = 0;
      
      $Item = self::readUInt32 ($Data, $Offset, $Length);
      $Count = self::readUInt32 ($Data, $Offset, $Length);
      
      // Commit to this instance
      $this->Item = $Item;
      $this->Count = $Count;
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
      return
        self::writeUInt32 ($this->Item) .
        self::writeUInt32 ($this->Count);
    }
    // }}}
  }
