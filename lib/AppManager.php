<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Console\Applications;

/**
 * Description of AppManager
 *
 * @author igs03102
 */
class AppManager {
    private $_config;
    
    private $_excluded = array('lib','src', 'vendor','nbproject', 'theme');
    
    function __construct($config) {
        $this->_config = $config;
    }
    
    function get_applications() {
        return $this->_config->em->getRepository("App")->findAll();
        
    
    }
    //put your code here
    function scan_for_new_apps() {
        $dir = scandir($this->_config->dirroot);
        $newapps = array();
        foreach($dir as $i) {
            if ($i[0] == ".") {
                continue;
            }
            if (!in_array($i,$this->_excluded) && is_dir($i)) {
                $app = $this->_config->em->getRepository("App")->findOneBy(array('path' => $i));
                if ($app) {
                    //already registered
                } else {
                    $newapps[] = $i;
                }
                //find out if registered already
            }
        }
        if (count($newapps) ==0 ) {
            return false;
        }
        //var_dump($newapps);
        return $newapps;
    }
    
    function install_new_apps() {
        
        $newapps = $this->scan_for_new_apps();
        if ($newapps === false) {
            return;
        }
        
        foreach($newapps as $path) {
            //load Application infor
            //save to Database
            require_once($path.'/appinfo.php');
            $classname = "app_{$path}";
            $i = new $classname();
            
            $a = new \App();
            $a->setName($i->getName());
            $a->setPath($path);
	    $a->setPanelImage('');
            $this->_config->em->persist($a);
            $this->_config->em->flush($a);
        }
        
    }

    /**
	* Returns the instance of the application
	* *not* the DB record.
	*/
    static function getAppInstance($path) {
	global $CFG;
	$base = $CFG->dirroot;
	require_once($base.'/'.$path.'/appinfo.php');
	$classname = "app_{$path}";
	$i = new $classname();
	return $i;
    }
}

interface IApplication {
    
    public function getName();

    public function dispatch($action);    
}

abstract class BaseApplication implements IApplication {
	private $name;
	public function getName() {
		return $this->name;
	}

	public function dispatch($action) {
		if (method_exists($this, 'dispatch_'.$action)) {
			return call_user_func_array(array($this, 'dispatch_'.$action), array());
		} 
//		return './';
		throw new \Exception('dispatch_'.$action .' does not exist');
	}
}
