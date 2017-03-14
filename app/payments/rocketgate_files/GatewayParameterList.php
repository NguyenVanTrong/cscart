<?php
/*
 * Copyright notice:
 * (c) Copyright 2007-2014 RocketGate LLC
 * All rights reserved.
 *
 * The copyright notice must not be removed without specific, prior
 * written permission from RocketGate LLC.
 *
 * This software is protected as an unpublished work under the U.S. copyright
 * laws. The above copyright notice is not intended to effect a publication of
 * this work.
 * This software is the confidential and proprietary information of RocketGate LLC.
 * Neither the binaries nor the source code may be redistributed without prior
 * written permission from RocketGate LLC.
 *
 * The software is provided "as-is" and without warranty of any kind, express, implied
 * or otherwise, including without limitation, any warranty of merchantability or fitness
 * for a particular purpose.  In no event shall RocketGate LLC be liable for any direct,
 * special, incidental, indirect, consequential or other damages of any kind, or any damages
 * whatsoever arising out of or in connection with the use or performance of this software,
 * including, without limitation, damages resulting from loss of use, data or profits, and
 * whether or not advised of the possibility of damage, regardless of the theory of liability.
 *
 */
////////////////////////////////////////////////////////////////////////////////
//
//	GatewayParameterList() - Object that holds name-value pairs
//				 that describe a request or response.
//
////////////////////////////////////////////////////////////////////////////////
//
class GatewayParameterList
{
  public $params;				// Name value pairs

  /**
   * GatewayParameterList constructor.
   */
  public function __construct()
  {
    $this->GatewayParameterList();
  }

//////////////////////////////////////////////////////////////////////
//
//	GatewayParameterList() - Constructor for class.
//
//////////////////////////////////////////////////////////////////////
//
  public function GatewayParameterList()
  {
    $this->params = array();			// Allocate an array
  }

//////////////////////////////////////////////////////////////////////
//
//	Reset() - Clear the elements in the array
//
//////////////////////////////////////////////////////////////////////
//
  public function Reset()
  {
    while (count($this->params) > 0) 		// Still some left?
      array_pop($this->params);			// Remove last element
  }

//////////////////////////////////////////////////////////////////////
//
//	Get() - Return the value associated with a key.
//
//////////////////////////////////////////////////////////////////////
//
  public function Get($key)
  {
    if (array_key_exists($key, $this->params)) {
      $value = $this->params[$key];		// Pull value from list
      $value = trim($value);			// Clean-up the string

      return $value;				// And return it to caller
    }

    return NULL;				// Key was not found
  }

//////////////////////////////////////////////////////////////////////
//
//	Set() - Set the value associated with a key.
//
//////////////////////////////////////////////////////////////////////
//
  public function Set($key, $value)
  {
    $this->Clear($key);				// Remove existing value
    $this->params[$key] = $value;		// Save new value
  }

//////////////////////////////////////////////////////////////////////
//
//	Clear() - Remove a key value.
//
//////////////////////////////////////////////////////////////////////
//
  public function Clear($key)
  {
    if (array_key_exists($key, $this->params))	// Does it exist?
      unset($this->params[$key]);		// Clear it
  }

//////////////////////////////////////////////////////////////////////
//
//	DebugPrint() - Dump the contents of the object
//		       for debugging.
//
//////////////////////////////////////////////////////////////////////
//
  public function DebugPrint()
  {
    print_r($this->params);
  }
}
