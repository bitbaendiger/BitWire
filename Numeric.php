<?PHP

  /**
   * BitWire - Numeric
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
  
  // Make sure GMP is available
  if (!extension_loaded ('gmp') && (!function_exists ('dl') || !dl ('gmp.so')))
    return;
  
  abstract class BitWire_Numeric {
    // {{{ BitWire_Numeric
    /**
     * Create a big number from compact representation
     * 
     * @param int $compactNumber
     * 
     * @access public
     * @return GMP
     **/
    public static function fromCompact ($compactNumber) : GMP {
      return gmp_init ((int)$compactNumber & 0xFFFFFF) * gmp_pow (256, ((((int)$compactNumber >> 24) & 0xFF) - 3));
    }
    // }}}
    
    // {{{ fromHash
    /**
     * Create a big number from hash
     * 
     * @param BitWire_Hash $sourceHash
     * 
     * @access public
     * @return GMP
     **/
    public static function fromHash (BitWire_Hash $sourceHash) : GMP {
      return gmp_import ($sourceHash->toBinary ());
    }
    // }}}
  }

?>