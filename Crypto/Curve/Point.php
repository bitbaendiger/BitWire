<?PHP

  class BitWire_Crypto_Curve_Point {
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
     * @param BitWire_Crypto_Curve $Curve
     * @param string $Key
     * 
     * @access public
     * @return BitWire_Crypto_Curve_Point
     **/
    public static function fromPublicKey (BitWire_Crypto_Curve $Curve, $Key) : ?BitWire_Crypto_Curve_Point {
      // Get the length of the key
      if (($Length = strlen ($Key)) < 1)
        return null;
      
      // Get the format of the key
      $Type = ord ($Key [0]);
      
      if (((($Type == 0x02) || ($Type == 0x03)) && ($Length != 33)) ||
          (($Type == 0x04) && ($Length != 65)) ||
          ($Type < 0x02) || ($Type > 0x04))
        return null;
      
      // Generate the values
      $x = gmp_import (substr ($Key, 1, 32));
      
      if (($Type == 0x02) || ($Type == 0x03))
        return static::fromCompressed ($Curve, $x, ($Type == 0x03));
      
      return new static ($Curve, $x, gmp_import (substr ($Key, 33, 32)));
    }
    // }}}
    
    // {{{ fromCompressed
    /**
     * Create a point from compressed representation
     * 
     * @param BitWire_Crypto_Curve $Curve
     * @param GMP $x
     * @param bool $Negative (optional)
     * @param GMP $order (optional)
     * 
     * @access public
     * @return Point
     **/
    public static function fromCompressed (BitWire_Crypto_Curve $Curve, GMP $x, $Negative = false, GMP $order = null) : BitWire_Crypto_Curve_Point {
      // Check if we were initialized before
      if (self::$g1 === null)
        self::init ();
      
      $y = gmp_powm (
        gmp_mod (gmp_add (gmp_add (gmp_powm ($x, self::$g3, $Curve->p), gmp_mul ($Curve->a, $x)), $Curve->b), $Curve->p),
        gmp_div_q (gmp_add ($Curve->p, self::$g1), self::$g4),
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
     * @param BitWire_Crypto_Curve $Curve
     * @param GMP $x
     * @param GMP $y
     * @param GMP $order (optional)
     * 
     * @access friendly
     * @return void
     **/
    function __construct (BitWire_Crypto_Curve $Curve, GMP $x, GMP $y, GMP $order = null) {
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
      $this->order = clone $this->order;
    }
    // }}}
    
    // {{{ getOrder
    /**
     * Retrive the order of this point
     * 
     * @access public
     * @return GMP
     **/
    public function getOrder () : ?GMP {
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
     * @return BitWire_Crypto_Curve_Point
     **/
    function double ($New = false) : BitWire_Crypto_Curve_Point {
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
     * @param BitWire_Crypto_Curve_Point $Add
     * @param bool $New (optional)
     * 
     * @access public
     * @return BitWire_Crypto_Curve_Point
     **/
    public function add (BitWire_Crypto_Curve_Point $Add, $New = false) : BitWire_Crypto_Curve_Point {
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
     * @param GMP $f
     * 
     * @access public
     * @return BitWire_Crypto_Curve_Point
     **/
    public function mul (GMP $f) : BitWire_Crypto_Curve_Point {
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
    public function toPublicKey ($Compressed = false) {
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
    public function validate () {
      $x = gmp_mod (gmp_add (gmp_add (gmp_powm ($this->x, self::$g3, $this->Curve->p), gmp_mul ($this->Curve->a, $this->x)), $this->Curve->b), $this->Curve->p);
      $y = gmp_mod (gmp_pow ($this->y, 2), $this->Curve->p);

      return (gmp_cmp ($x, $y) == 0);
    }
    // }}}
  }

?>