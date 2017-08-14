<?PHP

  require_once ('BitWire/Interface/Hashable.php');
  
  abstract class BitWire_Hashable implements BitWire_Interface_Hashable {
    // {{{ getHash
    /**
     * Retrive a hash for this object
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public function getHash () {
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