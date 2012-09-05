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

class lw_de_directory extends projectBasis 
{
    function __construct() 
    {
    }

    /**
     * Verteilerfunktion
     * 
     * @param boolean $isLoggedIn
     * @throws Exception 
     */
    function execute($isLoggedIn) 
    {
        if($isLoggedIn == true) {
            switch ($this->request->getAlnum("cmd")) {
                case "new" :
                    if (is_dir($this->directoryObject->getActualPath().$this->request->getRaw("name"))) {
                        $options = array(
                            "error" => "adddir",
                            "selecteddir" => $this->directoryObject->getRelativePath(),
                            "dirname" => $this->request->getRaw("name")
                        );
                        lw_object::pageReload($this->buildLink("navigation", "show", $this->directoryObject->getRelativePath(), false, $options));
                    } 
                    else {
                        $this->addNewDir();
                    }
                    break;

                case "edit" :
                    $parentObject = $this->directoryObject->getActualParentObject();
                    if (is_dir($parentObject->getActualPath().$this->request->getRaw("rename"))) {
                        $options = array(
                            "error" => "dirrename",
                            "selecteddir" => $this->directoryObject->getRelativePath(),
                            "rename" => $this->request->getRaw("rename")
                        );
                        lw_object::pageReload($this->buildLink("navigation", "show", $this->directoryObject->getRelativePath(), false, $options));
                    } 
                    else {
                        $this->rename();
                    }
                    break;

                case "delete" :
                    $this->delete();
                    break;
                
                default:
                    throw new Exception('unknown command in module "directory": '.$this->request->getAlnum("cmd"));

            }
        }
    }

    /**
     * Das ausgewählte Verzeichnis wird umbenannt, wenn es sich in der Max.
     * Verzeichnistiefe befindet.
     * 
     * @throws Exception 
     */
    function rename()
    {
        if($this->checkDirLevel($this->directoryObject->getRelativePath()) == true){
            $this->directoryObject->rename($this->request->getRaw("rename"));
            $this->redirectToParentList();
        }
        else{
            throw new Exception("rename dir not allowed");
        }
    }

    /**
     * Das ausgewählte Verzeichnis wird gelöscht, wenn es sich in der Max.
     * Verzeichnistiefe befindet.
     * 
     * @throws Exception 
     */
    function delete() 
    {
        if($this->checkDirLevel($this->directoryObject->getRelativePath()) == true){
            $files = $this->directoryObject->getDirectoryContents("file");
            $directories = $this->directoryObject->getDirectoryContents("dir");

            if (empty($directories) && empty($files)) {
                $this->directoryObject->delete();
                $this->redirectToParentList();
            } 
            else {
                if ($this->request->getAlnum("confirm") == 1){
                    $this->directoryObject->delete(TRUE);
                    $this->redirectToParentList();
                } 
                else {
                    if ($this->request->getRaw("reldir") == "home") {
                        $options = array(
                            "confirm" => "dirdeletion",
                            "reldir" => $this->directoryObject->getActualParentObject()->getRelativePath(),
                            "delete" => $this->directoryObject->getName()
                        );
                        lw_object::pageReload($this->buildLink("navigation", "show", $this->directoryObject->getRelativePath(), false, $options));
                    }
                    else {
                        $this->redirectToActualList();
                    }
                }
            }
        }
        else{
            throw new Exception("delete dir not allowed");
        }
    }

    /**
     * In das ausgewählte Verzeichnis wird ein neues Verzeichnis angelegt.
     * 
     * @throws Exception 
     */
    function addNewDir()
    {
        if($this->checkDirLevel($this->directoryObject->getRelativePath()) == true){
            $this->directoryObject->add($this->request->getAlnum("name"));
            $this->redirectToActualList();
        }
        else{
            throw new Exception("add new dir not allowed");
        }
    }
}