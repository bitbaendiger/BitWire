<?PHP

  /**
   * BitWire - DarkSend Election-Entry-Plus Message
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
  
  require_once ('BitWire/Message/DarkSend/ElectionEntry.php');
    
  class BitWire_Message_DarkSend_ElectionEntryPlus extends BitWire_Message_DarkSend_ElectionEntry {
    const PAYLOAD_COMMAND = 'dsee+';
    const DSEE_FORCE_TYPE = BitWire_Message_DarkSend_ElectionEntry::TYPE_DONATION;
  }
  
  // Register this payload
  BitWire_Message_Payload::registerCommand ('dsee+', 'BitWire_Message_DarkSend_ElectionEntryPlus');

?>