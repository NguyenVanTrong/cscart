<?php

////////////////////////////////////////////////////////////////////////////////
//
//	GatewayChecksum() - Static class for checksum and version.
//
////////////////////////////////////////////////////////////////////////////////
//
class GatewayChecksum
{
  public static $checksum = "";
  public static $baseChecksum = "3920736670ef6d59b63251a9a6987564";
  public static $versionNo = "P3.7";

//////////////////////////////////////////////////////////////////////
//
//	Set the client version number.
//
//////////////////////////////////////////////////////////////////////
//
  public static function SetVersion()
  {
    $dirName = dirname(__FILE__);
    $baseString = md5_file($dirName . "/GatewayService.php") .
          md5_file($dirName . "/GatewayRequest.php") .
          md5_file($dirName . "/GatewayResponse.php") .
          md5_file($dirName . "/GatewayParameterList.php") .
          md5_file($dirName . "/GatewayCodes.php");
    GatewayChecksum::$checksum = md5($baseString);
    if (GatewayChecksum::$checksum != GatewayChecksum::$baseChecksum)
      GatewayChecksum::$versionNo = "P3.7m";
  }
}
