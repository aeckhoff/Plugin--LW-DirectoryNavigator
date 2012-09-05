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

class lw_directorynavigator extends lw_plugin
{
    function __construct()
    {
        parent::__construct();
        $this->auth = lw_registry::getInstance()->getEntry("auth");
        $this->in_auth = lw_in_auth::getInstance();
    }
    
    /**
     * Es wird geprüft, ob der Nutzer eingeloggt ist oder nicht.
     * 
     * @return boolean 
     */
    function isLoggedIn()
    {
        if ($this->auth->isLoggedIn() || $this->in_auth->isLoggedIn()) {
            return true;
        }
        return false;
    }    
    
    /**
     * Die Ausgabe für das Frontend wird zurückgegeben. 
     * 
     * @return string
     * @throws Exception 
     */
    function buildPageOutput() 
    {
        $this->existLwDirectorynavigatorDir();
        $this->existLwdirinfoInMainPluginDir();
        $this->checkUseOfCustomCss();
        
        $modul = $this->request->getAlnum("module");
        if (!$modul) {
            $modul = "navigation";
        }
        
        try {
            if (in_array($modul, array("directory", "file", "navigation"))) {
                return $this->getObject("lw_de_".$modul)->execute($this->isLoggedIn());
            }
            else {
                throw new Exception('unknown module');
            }
        }
        catch (Exception $e) {
            #return '<div class="lw_de_error">ERROR: '.$e->getMessage().'</div>';
            require_once dirname(__FILE__).'/classes/projectBasis.php';
            $projectBasis = $this->getObject("projectBasis");
            $template = file_get_contents(dirname(__FILE__) . '/templates/error.tpl.html');
            $tpl = new lw_te($template);
            $tpl->reg("errormessage", $e->getMessage());
            $tpl->reg("homelink" , $projectBasis->buildLink("navigation", "show"));
            return $tpl->parse();
        }
    }
    
    /**
     * Objekte werden instanziert und wichtige Variablen an das Objekt übergeben.
     * 
     * @param string $objectName
     * @return object 
     */
    function getObject($objectName)
    {
        require_once dirname(__FILE__).'/classes/projectBasis.php';
        require_once dirname(__FILE__).'/classes/'.$objectName.'.php';
        
        $object = new $objectName();
        $object->setRequest($this->request);
        $object->setDatahandler($this->datahandler);
        $object->setDataBasehandler($this->db);
        $object->setConfig($this->config);
        $object->setResponse($this->response);

        $plugindata = $this->repository->plugins()->loadPluginData($this->getPluginName(), $this->params['oid']);
        
        $directoryObject = $this->getDirectoryObject($plugindata['parameter']['homedir'], $plugindata['parameter']['allowedExtensions']);
        $object->setDirectoryObject($directoryObject);

        if ($this->request->getRaw('file')) {
            $object->setFileObject($this->getFileObject($directoryObject));
        }
        
        $object->setMaxDirLevels($plugindata['parameter']['maxDirLevels']);
        $object->setUseOnlyHome_lwdirinfo($plugindata['parameter']['use_only_lwdirinfo_homedir']);
        $object->setTreeView($plugindata['parameter']['treeView']);
        $object->hideBreadcrumb($plugindata['parameter']['nobreadcrumb']);
        
        return $object;
    }
    
    /**
     * Die Instanz eines Dateiobjektes wird zurückgegeben.
     * 
     * @param object $directoryObject
     * @return object 
     */
    function getFileObject($directoryObject) 
    {
        require_once dirname(__FILE__).'/classes/lw_de_fileObject.php';
        $fileObject = lw_de_fileObject::getInstance($directoryObject, $this->request->getRaw('file'), $this->config);
        return $fileObject;
    }
    
    
    /**
     * Die Instanz eines Verzeichnisobjektes wird zurückgegeben.
     * 
     * @param string $homedir
     * @param string $allowedExtensions
     * @return object 
     */
    function getDirectoryObject($homedir, $allowedExtensions)
    {
        require_once dirname(__FILE__).'/classes/lw_de_directoryObject.php';
        $directoryObject = lw_de_directoryObject::getInstance($this->request->getRaw('dir'), $homedir, $this->config);
        $directoryObject->setAllowedExtensions($allowedExtensions);
        return $directoryObject;
    }
    
    /**
     * Backendausgabe wird zurückgegeben.
     * 
     * @return string 
     */
    function getOutput() 
    {
        require_once dirname(__FILE__).'/classes/projectBasis.php';
        require_once dirname(__FILE__).'/classes/backend.php';
        $backend = new backend();
        $backend->setRepository($this->repository);
        $backend->setRequest($this->request);
        $backend->setConfig($this->config);
        $backend->setPluginName($this->getPluginName());
        $backend->setOid($this->getOid());
        if ($this->request->getAlnum("pcmd") == "save"){
            $backend->backend_save();
        }
        return $backend->backend_view();
    }
    
    /**
     * Contentobjekt im Backend darf gelöscht werden oder nicht.
     * 
     * @return boolean 
     */
    function deleteEntry()
    {
        return true;
    }
    
    /**
     * Es wird geprüft ob das Plugin-Hauptverzeichnis vorhanden ist und ggf. erzeugt.
     */
    function existLwDirectorynavigatorDir()
    {
        require_once dirname(__FILE__).'/classes/projectBasis.php';
        $projectBasis = $this->getObject("projectBasis");

        $directory = lw_directory::getInstance($this->config["path"]["web_resource"]);
        $directories = $directory->getDirectoryContents("dir");
        foreach ($directories as $dir) {
            $contentArray[] = $dir->getName();
        }
        if (!(in_array("lw_directorynavigator/", $contentArray))) {
            $directory->add("lw_directorynavigator");
            lw_object::pageReload($projectBasis->buildLink("navigation", "show", "home"));
        }        
    }
    
    /**
     * Es wird geprüft, ob die "lwdirinfo" im Plugin-Hauptverzeichnis vorhanden ist und ggf. erstellt.
     */
    function existLwdirinfoInMainPluginDir()
    {
        $directory = lw_directory::getInstance($this->config["path"]["web_resource"] . "lw_directorynavigator/");
        $files = $directory->getDirectoryContents("file");
        if(!empty($files)){
            foreach ($files as $file) {
                $contentArray[] = $file->getFilename();
            }
            if (!(in_array("lwdirinfo.txt", $contentArray))) lw_io::writeFile($this->config["path"]["web_resource"] . "lw_directorynavigator/lwdirinfo.txt","Es ist kein Infotext vorhanden.");   
        }
        else {
            lw_io::writeFile($this->config["path"]["web_resource"] . "lw_directorynavigator/lwdirinfo.txt","Es ist kein Infotext vorhanden.");
        }
    }
    
    /**
     * Es wird geprüft, ob der Nutzer das Standardtemplate oder ein eigenes Template nutzen möchte.
     */
    function checkUseOfCustomCss()
    {
        $isUseCustomCssSet = $this->repository->plugins()->loadPluginData($this->getPluginName(), $this->params['oid']);
        if($isUseCustomCssSet['parameter']['use_custom_css'] == false) {
            $css = file_get_contents(dirname(__FILE__)."/css/lw_directorynavigator.css");
            $css = str_replace("{_imageurl_}", $this->config['url']['media']."pics/fatcow_icons", $css);
            $this->response->addHeaderItems("css", $css);
        }
    }
}
