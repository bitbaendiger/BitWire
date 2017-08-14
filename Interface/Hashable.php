<?PHP

  interface BitWire_Interface_Hashable {
    // {{{ getHash
    /**
     * Retrive a hash for this object
     * 
     * @access public
     * @return BitWire_Hash
     **/
    public function getHash ();
    // }}}
  }

?>