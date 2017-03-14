<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

namespace Tygh\ElFinder;

class Connector extends \elFinderConnector {

    protected function output(array $data) {
        $header = isset($data['header']) ? $data['header'] : $this->header;
        unset($data['header']);
        if ($header) {
            if (is_array($header)) {
                foreach ($header as $h) {
                    header($h);
                }
            } else {
                header($header);
            }
        }

        if (isset($data['pointer'])) {
            $chunksize = 1*(1024*1024); // 1MB
            rewind($data['pointer']);
            while (!feof($data['pointer'])) {
               echo(fread($data['pointer'], $chunksize));
               @ob_flush();
               flush();
            }
            if (!empty($data['volume'])) {
                $data['volume']->close($data['pointer'], $data['info']['hash']);
            }
            exit();
        } else {
            if (!empty($data['raw']) && !empty($data['error'])) {
                exit($data['error']);
            } else {
                exit(json_encode($data));
            }
        }

    }
}
