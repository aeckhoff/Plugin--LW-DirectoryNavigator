<?php

/**************************************************************************
*  Copyright notice
*
*  Copyright 2012 Logic Works GmbH
*
*  Licensed under the Apache License, Version 2.0 (the "License");
*  you may not use this file except in compliance with the License.
*  You may obtain a copy of the License at
*
*  http://www.apache.org/licenses/LICENSE-2.0
*  
*  Unless required by applicable law or agreed to in writing, software
*  distributed under the License is distributed on an "AS IS" BASIS,
*  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
*  See the License for the specific language governing permissions and
*  limitations under the License.
*  
***************************************************************************/

class lw_de_directoryObject 
{
    private $dir;
    private $homeDir;
    private $configuration;
    private $directoryObject = array();
    private $actualDirectoryObject;
    private $parentDirectoryObject;

    private function __construct($dir)
    {
        if ($dir == "home") {
            $dir = '';
        }
        $this->dir = $this->filterVar($dir);
    }
    
    public function getInstance($dir, $home, $config) 
    {
        if (!is_object($this->directoryObjectContainer[$dir.$home])) {
            $this->directoryObjectContainer[$dir.$home] = new lw_de_directoryObject($dir);
            $this->directoryObjectContainer[$dir.$home]->setHomeDir($home);
            $this->directoryObjectContainer[$dir.$home]->setConfiguration($config);
            $this->directoryObjectContainer[$dir.$home]->init();
        }
        return $this->directoryObjectContainer[$dir.$home];
    }
    
    public function setHomeDir($dir)
    {
        $this->homeDir = $dir;
    }
    
    public function setConfiguration($config)
    {
        $this->configuration = $config;
    }
    
    public function init()
    {
        $path = $this->configuration["path"]["web_resource"]."lw_directoryexplorer/".$this->homeDir.$this->dir;
        if (!is_writeable($path)) {
            throw new Exception("directory is not writeable: ".$path);
        }
        $realpatha = realpath($this->configuration["path"]["web_resource"]."lw_directoryexplorer/");
        $realpathb = realpath($path);
        
        if (substr($realpathb,0,strlen($realpatha)) != $realpatha) {
            throw new Exception("invalid directory: ".$path);
        }
        
        if (!is_dir($path)) {
            throw new Exception("invalid directory: ".$path);
        }
        $this->directoryObject = lw_directory::getInstance($path);
    }
    
    function getActualPath() 
    {
        return $this->directoryObject->getBasepath().$this->directoryObject->getName();
    }

    function getName()
    {
        $name = $this->directoryObject->getName();
        if (!$name) {
            return "home";
        }
        return $name;
    }
    
    function getActualParentObject()
    {
        if (!$this->dir) {
            return false;
        }
        if (!$this->parentObject) {
            $parentDir = str_replace($this->configuration["path"]["web_resource"]."lw_directoryexplorer/".$this->homeDir, "", $this->directoryObject->getBasepath());
            $this->parentObject = lw_de_directoryObject::getInstance($parentDir, $this->homeDir, $this->configuration); 
        }
        return $this->parentObject;
    }
 
    function getRelativePath()
    {
        if (!$this->relativePath) {
            $this->relativePath = str_replace($this->configuration["path"]["web_resource"]."lw_directoryexplorer/".$this->homeDir, "", $this->getActualPath());
            if (substr($this->relativePath, -1) == "/") {
                $this->relativePath =substr($this->relativePath, 0, -1);
            }
        }
        return $this->relativePath;
    }
    
    function filterVar($var) 
    {
        $var = str_replace("..", "", $var);
        $var = str_replace("//", "", $var);
        $var = preg_replace("/[^A-Z .\/a-z0-9_-]/", "", $var);
        return $var;
    }
    
    function getDirectoryContents($type=false) 
    {
        return $this->directoryObject->getDirectoryContents($type);
    }

    function rename($name) 
    {
        return $this->directoryObject->rename($name);
    }
    
    function add($name) 
    {
        return $this->directoryObject->add($name);
   }
    
    function addFile($temp, $file) 
    {
        return $this->directoryObject->addFile($temp, $file);
    }
    
    function getNextFilename($file) 
    {
        return $this->directoryObject->getNextFilename($file);
    }
    
    function delete($flag=false) 
    {
        return $this->directoryObject->delete($flag);
    }
    
    function getInfoFileContent()
    {
        if (!$this->infoContent && is_file($this->getActualPath()."/lwdirinfo.txt")) {
            $this->infoContent = file_get_contents($this->getActualPath()."/lwdirinfo.txt");
        }
        return $this->infoContent;
    }
    
    function writeInfoFileContent($content)
    {
        lw_io::writeFile($this->getActualPath()."/lwdirinfo.txt", $content);
    }
    
    function setAllowedExtensions($extensionString)
    {   
        $this->allowedExtensionArray = explode(",", $extensionString);
        $this->disabledExtensionArray = explode(",", $this->configuration["directoryexplorer"]["disabled_extensions"]);
    }
    
    function isExtensionAllowed($extension)
    {
        if (in_array($extension, $this->disabledExtensionArray)) {
            return false;
        }
        if ($this->allowedExtensionArray[0] == "*" || in_array($extension, $this->allowedExtensionArray)) {
            return true;
        }
        return false;
    }    
}