<?PHP

  /**
   * BitWire - Hashable Message Payload
   * Copyright (C) 2019-2020 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  abstract class BitWire_Message_Payload_Hashable extends BitWire_Message_Payload {
    // {{{ getHash
    /**
     * Retrive the hash for this payload
     * 
     * @access public
     * @return BitWire_Hash
     **/
    abstract public function getHash () : BitWire_Hash;
    // }}}
  }

?>