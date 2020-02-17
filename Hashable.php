<?PHP

  /**
   * BitWire - Hashable
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
  
  require_once ('BitWire/Interface/Hashable.php');
  
  abstract class BitWire_Hashable implements BitWire_Interface_Hashable {
    // {{{ getHash
    /**
     * Retrive a hash for this object
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public function getHash () : BitWire_Hash {
      return new BitWire_Hash ($this->toBinary ());
    }
    // }}}
    
    // {{{ toBinary
    /**
     * Create a binary representation of this object
     * 
     * @access public
     * @return string
     **/
    abstract public function toBinary ();
    // }}}
  }

?>