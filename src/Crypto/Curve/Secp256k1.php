<?php

  /**
   * BitWire - ECDSA Curve
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

  namespace BitBaendiger\BitWire\Crypto\Curve;
  use \BitBaendiger\BitWire\Crypto;
  
  class Secp256k1 extends Crypto\Curve {
    private static $default = null;
    
    public $G = null;
    
    public static function singleton () : Secp256k1 {
      if (!self::$default)
        self::$default = new Secp256k1 ();
      
      return self::$default;
    }
    
    function __construct () {
      // Setup this curve
      parent::__construct (
        gmp_init ('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F', 16),
        gmp_init ('0000000000000000000000000000000000000000000000000000000000000000', 16),
        gmp_init ('0000000000000000000000000000000000000000000000000000000000000007', 16)
      );
      
      $this->m = gmp_init (1);
      $this->n = gmp_init ('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141', 16);
      
      // Setup generator-point
      $this->G = new Point (
        $this,
        gmp_init ('79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798', 16),
        gmp_init ('483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8', 16),
        $this->n
      );
    }
  }
