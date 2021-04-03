<?php

  /**
   * BitWire - Numeric
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

  namespace BitBaendiger\BitWire;
  
  abstract class Numeric {
    // {{{ BitWire_Numeric
    /**
     * Create a big number from compact representation
     * 
     * @param int $compactNumber
     * @param bool $isNegative (optional)
     * @param bool $isOverflow (optional)
     * 
     * @access public
     * @return \GMP
     **/
    public static function fromCompact (int $compactNumber, bool &$isNegative = false, bool &$isOverflow = false) : \GMP {
      $nBytes = (((int)$compactNumber >> 24) & 0xff);
      $nWord = (int)$compactNumber & 0x007fffff;
      $rBase = gmp_init ($nWord);
      
      if ($nBytes <= 3)
        $rBase >>= (8 * (3 - $nBytes));
      else
        $rBase <<= (8 * ($nBytes - 3));
      
      $isNegative = (($nWord != 0) && (($compactNumber & 0x00800000) != 0));
      $isOverflow =
        (($nWord != 0) &&
         (
          ($nBytes > 34) ||
          (($nBytes > 33) && ($nWord > 0xff)) ||
          (($nBytes > 32) && ($nWord > 0xffff))
         )
        );
      
      return $rBase;
    }
    // }}}
    
    // {{{ toCompact
    /**
     * Convert a big number to compact representation
     * 
     * @param \GMP $sourceNumber
     * @param bool $isNegative (optional)
     * 
     * @access public
     * @return int
     **/
    public static function toCompact (\GMP $sourceNumber, bool $isNegative = false) : int {
      $nBytes = floor ((static::getSize ($sourceNumber) + 7) / 8);
      
      if ($nBytes <= 3)
        $nCompact = $sourceNumber << (8 * (3 - $nBytes));
      else
        $nCompact = $sourceNumber >> (8 * ($nBytes - 3));
      
      $nCompact = gmp_intval ($nCompact) & 0x00ffffff;
      
      if ($nCompact & 0x00800000) {
        $nCompact >>= 8;
        $nBytes++;
      }
      
      $nCompact |= ($nBytes << 24);
      $nCompact |= ($isNegative && ($nCompact & 0x007fffff) ? 0x00800000 : 0x00000000);
      
      return $nCompact;
    }
    // }}}
    
    // {{{ fromHash
    /**
     * Create a big number from hash
     * 
     * @param Hash $sourceHash
     * 
     * @access public
     * @return \GMP
     **/
    public static function fromHash (Hash $sourceHash) : \GMP {
      return gmp_import ($sourceHash->toBinary ());
    }
    // }}}
    
    // {{{ getSize
    /**
     * Retrive the size in bits of a big number
     * 
     * @param \GMP $sourceNumber
     * 
     * @access public
     * @return int
     **/
    public static function getSize (\GMP $sourceNumber) : int {
      // Find the most significat bit
      $currentSize = 0;
      
      while (($nextSize = gmp_scan1 ($sourceNumber, $currentSize + 1)) > $currentSize)
        $currentSize = $nextSize;
      
      // Return last known size
      return $currentSize;
    }
    // }}}
  }
