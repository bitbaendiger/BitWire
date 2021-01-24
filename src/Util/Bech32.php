<?PHP

  namespace BitBaendiger\BitWire\Util;
  
  /**
   * BitWire - Bech32 Functions
   * Copyright (C) 2021 Bernd Holzmueller <bernd@quarxconnect.de>
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
  
  class Bech32 {
    /* Bech32 Charset */
    const CHARSET = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';
    
    // {{{ encode
    /**
     * Generate bech32-string from a binary string
     * 
     * @param string $humanReadablePart
     * @param string $inputString
     * @param array $bechPrefix (optional)
     * 
     * @access public
     * @return string
     **/
    public static function encode ($humanReadablePart, $inputString, array $bechPrefix = array ()) {
      // Convert input to uint8_t-array
      $inputValues = self::convertStringToArray ($inputString);
      unset ($inputString);
      
      // Convert Base256 to Base32
      $convertedValues = $bechPrefix;
      $currentValue = 0;
      $currentBits = 0;
      
      foreach ($inputValues as $inputValue) {
        $currentValue = ($currentValue << 8) | $inputValue;
        $currentBits += 8;
        
        while ($currentBits >= 5) {
          $currentBits -= 5;
          $convertedValues [] = ($currentValue >> $currentBits) & ((1 << 5) - 1);
        }
      }
      
      if ($currentBits > 0)
        $convertedValues [] = ($currentValue << 3) & ((1 << 5) - 1);
      
      $inputValues = $convertedValues;
      unset ($convertedValues, $currentValue, $currentBits);
      
      // Expand Human-Readable-Part
      $hrpLength = strlen ($humanReadablePart);
      $expandedHRP = array_fill (0, $hrpLength * 2 + 1, 0x00);
      
      for ($i = 0; $i < $hrpLength; $i++) {
        $c = ord ($humanReadablePart [$i]);
        
        $expandedHRP [$i] = $c >> 5;
        $expandedHRP [$i + $hrpLength + 1] = $c & 0x1F;
      }
      
      // Create Checksum
      $bechChecksum = self::bechChecksum (array_merge ($expandedHRP, $inputValues, array_fill (0, 6, 0x00)));
      
      // Generate output
      $bechResult = $humanReadablePart . '1';
      
      foreach (array_merge ($inputValues, $bechChecksum) as $bechValue)
        $bechResult .= self::CHARSET [$bechValue];
      
      return $bechResult;
    }
    // }}}
    
    // {{{ convertStringToArray
    /**
     * Convert a string to an array of uint8_t
     * 
     * @param string $inputString
     * 
     * @access private
     * @return array
     **/
    private static function convertStringToArray ($inputString) : array {
      $outputArray = [ ];
      
      for ($i = 0; $i < strlen ($inputString); $i++)
        $outputArray [] = ord ($inputString [$i]);
      
      return $outputArray;
    }
    // }}}
    
    // {{{ bechChecksum
    /**
     * Generate bech32-checksum for an array of input-values
     * 
     * @access private
     * @return array
     **/
    private static function bechChecksum (array $inputValues) : array {
      $polyMod = 1;
      
      for ($i = 0; $i < count ($inputValues); $i++) {
        $polyMod0 = $polyMod >> 25;
        $polyMod = ((($polyMod & 0x1ffffff) << 5) ^ $inputValues [$i]) & 0xFFFFFFFF;
        
        if ($polyMod0 & 1)  $polyMod ^= 0x3b6a57b2;
        if ($polyMod0 & 2)  $polyMod ^= 0x26508e6d;
        if ($polyMod0 & 4)  $polyMod ^= 0x1ea119fa;
        if ($polyMod0 & 8)  $polyMod ^= 0x3d4233dd;
        if ($polyMod0 & 16) $polyMod ^= 0x2a1462b3;
      }
      
      $polyMod ^= 1;
      $bechChecksum = [ ];
      
      for ($i = 0; $i < 6; ++$i) {
        $bechChecksum [$i] = ($polyMod >> (5 * (5 - $i))) & 31;
      }
      
      return $bechChecksum;
    }
    // }}}
  }

?>
