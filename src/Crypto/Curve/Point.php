<?php

  /**
   * BitWire - ECDSA Curve Point
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
  
  class Point {
    private static $g1 = null;
    private static $g2 = null;
    private static $g3 = null;
    private static $g4 = null;

    public $Curve, $x, $y;
    
    private $order = null;
    
    // {{{ fromPublicKey
    /**
     * Extract a curve-point from a public key
     * 
     * @param Crypto\Curve $Curve
     * @param string $Key
     * @param \GMP $order (optional)
     * 
     * @access public
     * @return Point
     **/
    public static function fromPublicKey (Crypto\Curve $Curve, string $Key, \GMP $order = null) : Point {
      // Get the length of the key
      if (($Length = strlen ($Key)) < 1)
        throw new \LengthException ('Short read');
      
      // Get the format of the key
      $Type = ord ($Key [0]);
      
      if (((($Type == 0x02) || ($Type == 0x03)) && ($Length != 33)) ||
          (($Type == 0x04) && ($Length != 65)) ||
          ($Type < 0x02) || ($Type > 0x04))
        return null;
      
      // Generate the values
      $x = gmp_import (substr ($Key, 1, 32));
      
      if (($Type == 0x02) || ($Type == 0x03)) {
        $Point = static::fromCompressed ($Curve, $x, ($Type == 0x03), $order);
        
        // UGLY HACK: Check twice if imported key exports to the same
        if (strcmp ($Point->toPublicKey (true), $Key) != 0) {
          $Point2 = static::fromCompressed ($Curve, $x, ($Type != 0x03), $order);
          
          if (strcmp ($Point2->toPublicKey (true), $Key) == 0)
            return $Point2;
          
          trigger_error ('Export does not equal import, inverting was unsuccessfull');
        }
        
        return $Point;
      }
      
      return new static ($Curve, $x, gmp_import (substr ($Key, 33, 32)), $order);
    }
    // }}}
    
    // {{{ fromCompressed
    /**
     * Create a point from compressed representation
     * 
     * @param Crypto\Curve $Curve
     * @param \GMP $x
     * @param bool $Negative (optional)
     * @param \GMP $order (optional)
     * 
     * @access public
     * @return Point
     **/
    public static function fromCompressed (Crypto\Curve $Curve, \GMP $x, bool $Negative = false, \GMP $order = null) : Point {
      // Check if we were initialized before
      if (self::$g1 === null)
        self::init ();
      
      $y = gmp_powm (
        gmp_mod (gmp_powm ($x, self::$g3, $Curve->p) + gmp_mul ($Curve->a, $x) + $Curve->b, $Curve->p),
        gmp_div_q ($Curve->p + self::$g1, self::$g4),
        $Curve->p
      );
      
      if (!$Negative)
        return new static ($Curve, $x, gmp_mod (gmp_sub ($Curve->p, $y), $Curve->p), $order);
      
      return new static ($Curve, $x, $y, $order);
    }
    // }}}
    
    // {{{ init
    /**
     * Setup some static stuff
     * 
     * @access private
     * @return void
     **/
    private static function init () {
      self::$g1 = gmp_init (1);
      self::$g2 = gmp_init (2);
      self::$g3 = gmp_init (3);
      self::$g4 = gmp_init (4);
    }
    // }}}
    
    // {{{ __construct
    /**
     * Create a new point
     * 
     * @param Crypto\Curve $Curve
     * @param \GMP $x
     * @param \GMP $y
     * @param \GMP $order (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (Crypto\Curve $Curve, \GMP $x, \GMP $y, \GMP $order = null) {
      // Check if we were initialized before
      if (self::$g1 === null)
        self::init ();

      // Setup ourself
      $this->Curve = $Curve;
      $this->x = $x;
      $this->y = $y;
      $this->order = $order;
    }
    // }}}

    // {{{ __clone
    /**
     * Clone this point
     * 
     * @access friendly
     * @return void
     **/
    function __clone () {
      $this->x = clone $this->x;
      $this->y = clone $this->y;
      
      if ($this->order !== null)
        $this->order = clone $this->order;
    }
    // }}}
    
    // {{{ getOrder
    /**
     * Retrive the order of this point
     * 
     * @access public
     * @return \GMP
     **/
    public function getOrder () : ?\GMP {
      return $this->order;
    }
    // }}}

    // {{{ double
    /**
     * Double this point
     * 
     * @param bool $New (optional)
     * 
     * @access public
     * @return Curve\Point
     **/
    function double (bool $New = false) : Curve\Point {
      if ($New) {
        $New = clone $this;
        $New->double ();

        return $New;
      }

      $l = gmp_mod (gmp_mul (gmp_invert (gmp_mod (gmp_mul (self::$g2, $this->y), $this->Curve->p), $this->Curve->p), gmp_add (gmp_mul (self::$g3, gmp_pow ($this->x, 2)), $this->Curve->a)), $this->Curve->p);
      $x = gmp_mod (gmp_sub (gmp_sub (gmp_pow ($l, 2), $this->x), $this->x), $this->Curve->p);
      $y = gmp_mod (gmp_sub (gmp_mul ($l, gmp_sub ($this->x, $x)), $this->y), $this->Curve->p);

      $this->x = $x;
      $this->y = $y;

      return $this;
    }
    // }}}

    // {{{ add
    /**
     * Add another point to this one
     * 
     * @param Point $Add
     * @param bool $New (optional)
     * 
     * @access public
     * @return Point
     **/
    public function add (Point $Add, bool $New = false) : Point {
      if ($New) {
        $New = clone $this;
        $New->add ($Add);

        return $New;
      }

      if ((gmp_cmp ($this->x, $Add->x) == 0) && (gmp_cmp ($this->y, $Add->y) == 0))
        return $this->double ();

      $l = gmp_mod (gmp_mul (gmp_sub ($this->y, $Add->y), gmp_invert (gmp_sub ($this->x, $Add->x), $this->Curve->p)), $this->Curve->p);
      $x = gmp_mod (gmp_sub (gmp_sub (gmp_pow ($l, 2), $this->x), $Add->x), $this->Curve->p);
      $y = gmp_mod (gmp_sub (gmp_mul ($l, gmp_sub ($this->x, $x)), $this->y), $this->Curve->p);

      $this->x = $x;
      $this->y = $y;

      return $this;
    }
    // }}}
    
    // {{{ mul
    /**
     * Multiply point with a number
     * 
     * @param \GMP $f
     * 
     * @access public
     * @return Point
     **/
    public function mul (\GMP $f) : Point {
      // Find topmost bit
      $bits = 0;

      while (($nbit = gmp_scan1 ($f, $bits)) >= $bits)
        $bits = $nbit + 1;

      if ($bits > 1)
        $bits--;

      // Generate the result
      $Result = clone $this;

      for ($bit = $bits - 1; $bit >= 0; $bit--) {
        $Result->double ();

        if (gmp_testbit ($f, $bit))
          $Result->add ($this);
      }

      return $Result;
    }
    // }}}

    // {{{ toPublicKey
    /**
     * Generate a public key from this point
     * 
     * @param bool $Compressed
     * 
     * @access public
     * @return string
     **/
    public function toPublicKey (bool $Compressed = false) : string {
      // Preapre x for export
      $x = gmp_export ($this->x);
      $lx = strlen ($x);

      if ($lx < 32)
        $x = str_repeat ("\x00", 32 - $lx) . $x;

      // Export compressed key
      if ($Compressed)
        return (gmp_cmp (gmp_mod ($this->y, self::$g2), self::$g1) == 0 ? "\x03" : "\x02") . $x;

      // Prepare y for export
      $y = gmp_export ($this->y);
      $ly = strlen ($y);

      if ($ly < 32)
        $y = str_repeat ("\x00", 32 - $ly) . $y;

      // Export full key
      return "\x04" . $x . $y;
    }
    // }}}

    // {{{ validate
    /**
     * Make sure this point is on the curve
     * 
     * @access public
     * @return bool
     **/
    public function validate () : bool {
      $x = gmp_mod (gmp_add (gmp_add (gmp_powm ($this->x, self::$g3, $this->Curve->p), gmp_mul ($this->Curve->a, $this->x)), $this->Curve->b), $this->Curve->p);
      $y = gmp_mod (gmp_pow ($this->y, 2), $this->Curve->p);

      return (gmp_cmp ($x, $y) == 0);
    }
    // }}}
  }
