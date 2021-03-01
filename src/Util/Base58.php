<?php

  /**
   * BitWire - Base58 Functions
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
  
  namespace BitBaendiger\BitWire\Util;
  
  class Base58 {
    private const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    
    // {{{ encode
    /**
     * Generate base58-string from a binary string
     * 
     * @param string $Data
     * 
     * @access public
     * @return string
     **/
    public static function encode (string $Data) : string {
      // Make sure GMP is available
      if (!extension_loaded ('gmp') && (!function_exists ('dl') || !dl ('gmp.so')))
        throw new \Error ('Missing GMP-Extension for base58-encoding');
      
      // Initialize
      $Number = gmp_import ($Data);
      $Base = gmp_init (58);
      $Result = '';
      
      // Generate base58-encoding
      while (gmp_cmp ($Number, $Base) >= 0) {
        $r = gmp_div_qr ($Number, $Base);
        
        $Result = self::ALPHABET [gmp_intval ($r [1])] . $Result;
        $Number = $r [0];
      }
      
      if (($Number = gmp_intval ($Number)) > 0)
        $Result = self::ALPHABET [$Number] . $Result;
      
      // Process leading zeros
      $i = 0;
      
      while ($Data [$i++] == "\x00")
        $Result = '1' . $Result;
      
      return $Result;
    }
    // }}}
    
    // {{{ decode
    /**
     * Convert a base-58 encoded string into its binary representation
     * 
     * @param string $Data
     * 
     * @access public
     * @return string
     **/
    public static function decode (string $Data) : string {
      // Make sure GMP is available
      if (!extension_loaded ('gmp') && (!function_exists ('dl') || !dl ('gmp.so')))
        throw new \Error ('Missing GMP-Extension for base58-encoding');
      
      // Initialize
      $Result = gmp_init (0);
      $Base = gmp_init (58);
      
      // Decode
      for ($i = 0; $i < strlen ($Data); $i++) {
        if (($p = strpos (self::ALPHABET, $Data [$i])) === false)
          throw new \ValueError ('Invalid charater on input');
        
        $Result = gmp_add (gmp_mul ($Result, $Base), gmp_init ($p));
      }
      
      $Result = gmp_export ($Result);
      
      // Prefix with leading zeros
      for ($i = 0; $i < strlen ($Data); $i++)
        if ($Data [$i] == '1')
          $Result = "\x00" . $Result;
        else
          break;
      
      // Return the result
      return $Result;
    }
    // }}}
  }
