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

class Core extends \elFinder {

    public function __construct($opts) {
        if (session_id() == '') {
            session_start();
        }

        $this->time  = $this->utime();
        $this->debug = (isset($opts['debug']) && $opts['debug'] ? true : false);
        $this->timeout = (isset($opts['timeout']) ? $opts['timeout'] : 0);

        setlocale(LC_ALL, !empty($opts['locale']) ? $opts['locale'] : 'en_US.UTF-8');

        // bind events listeners
        if (!empty($opts['bind']) && is_array($opts['bind'])) {
            foreach ($opts['bind'] as $cmd => $handler) {
                $this->bind($cmd, $handler);
            }
        }

        if (!isset($opts['roots']) || !is_array($opts['roots'])) {
            $opts['roots'] = array();
        }

        // check for net volumes stored in session
        foreach ($this->getNetVolumes() as $root) {
            $opts['roots'][] = $root;
        }

        // "mount" volumes
        foreach ($opts['roots'] as $i => $o) {
            if (!empty($o['driver']) && strpos($o['driver'], '\\') !== false) {
                $class = $o['driver'];
            } else {
                $class = 'elFinderVolume'.(isset($o['driver']) ? $o['driver'] : '');
            }

            if (class_exists($class)) {
                $volume = new $class();

                if ($volume->mount($o)) {
                    // unique volume id (ends on "_") - used as prefix to files hash
                    $id = $volume->id();

                    $this->volumes[$id] = $volume;
                    if (!$this->default && $volume->isReadable()) {
                        $this->default = $this->volumes[$id];
                    }
                } else {
                    $this->mountErrors[] = 'Driver "'.$class.'" : '.implode(' ', $volume->error());
                }
            } else {
                $this->mountErrors[] = 'Driver "'.$class.'" does not exists';
            }
        }

        // if at least one redable volume - ii desu >_<
        $this->loaded = !empty($this->default);
    }

}
