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
      
      if (($Type == 0x02) || ($Type == 0x03))
        return static::fromCompressed ($Curve, $x, ($Type == 0x03), $order);
        
      return new static ($Curve, $x, gmp_import (substr ($Key, 33, 32)), $order);
    }
    // }}}
    
    // {{{ fromCompressed
    /**
     * Create a point from compressed representation
     * 
     * @param Crypto\Curve $onCurve
     * @param \GMP $x
     * @param bool $isOdd (optional)
     * @param \GMP $order (optional)
     * 
     * @access public
     * @return Point
     **/
    public static function fromCompressed (Crypto\Curve $onCurve, \GMP $x, bool $isOdd = false, \GMP $order = null) : Point {
      $y2 = (((($x**3) % $onCurve->p) + (($x * $onCurve->a) % $onCurve->p) + $onCurve->b) % $onCurve->p);
      $y = gmp_sqrt ($y2);
      
      # if ($y * $y != $y2)
      #   throw new \Exception ('Invalid number');
      
      if ($isOdd != ($y % 2 == 1))
        $y = (($onCurve->p - $y) % $onCurve->p);
      
      return new static ($onCurve, $x, $y, $order);
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
     * @return Point
     **/
    function double (bool $New = false) : Point {
      if ($New) {
        $New = clone $this;
        $New->double ();

        return $New;
      }

      $l = ((gmp_invert ((($this->y * 2) % $this->Curve->p), $this->Curve->p) * ((($this->x**2) * 3) + $this->Curve->a)) % $this->Curve->p);
      $x = ((($l**2) - ($this->x * 2)) % $this->Curve->p);
      $y = ((($l * ($this->x - $x)) - $this->y) % $this->Curve->p);

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

      if (($this->x == $Add->x) && ($this->y == $Add->y))
        return $this->double ();

      $l = ((($this->y - $Add->y) * gmp_invert (($this->x - $Add->x), $this->Curve->p)) % $this->Curve->p);
      $x = (((($l**2) - $this->x) - $Add->x) % $this->Curve->p);
      $y = ((($l * ($this->x - $x)) - $this->y) % $this->Curve->p);

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
        return ($this->y % 2 == 1 ? "\x03" : "\x02") . $x;

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
      $x = ((((($this->x**3) % $this->Curve->p) + ($this->Curve->a * $this->x)) + $this->Curve->b) % $this->Curve->p);
      $y = (($this->y**2) % $this->Curve->p);

      return ($x == $y);
    }
    // }}}
  }
