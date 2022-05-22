<?php
 
namespace App\Exceptions;
 
use Exception;
 
class FailedRecalculateException extends Exception {
    private $_data = null;

    public function __construct($message, $data)  {
        $this->_data = $data;
        parent::__construct($message);
    }

    public function context() {
        return (array)$this->_data;
    }
}