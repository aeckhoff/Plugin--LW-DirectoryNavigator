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

class projectBasis extends lw_object
{
    function setRequest($request)
    {
        $this->request= $request;
    }
    
    function setDatahandler($datahandler)
    {
        $this->datahandler = $datahandler;
    }
    
    function setDataBasehandler($databasehandler)
    {
        $this->db = $databasehandler;
    }
    
    function setConfig($config)
    {
        $this->config = $config;
    }
    
    function setLang($lang)
    {
        $this->lang = $lang;
    }
    
    function setRepository($repository)
    {
        $this->repository = $repository;
    }
    
    function setPluginName($pluginname)
    {
        $this->getPluginName = $pluginname;
    }
    
    function getPluginName()
    {
        return $this->getPluginName;
    }
    
    function setOid($oid)
    {
        $this->oid = $oid;
    }
    
    function getOid()
    {
        return $this->oid;
    }
    
    function setResponse($response)
    {
        $this->response = $response;
    }
    
    function setDirectoryObject($object)
    {
        $this->directoryObject = $object;
    }
    
    function setFileObject($object) 
    {
        $this->fileObject = $object;
    }
    
    function buildLink($module, $cmd, $dir=false, $file=false, $options=false)
    {
        $array['index'] = $this->request->getInt("index");
        $array['module'] = $module;
        $array['cmd'] = $cmd;
        if ($dir) {
            $array['dir'] = $dir;
        }
        if ($file) {
            $array['file'] = $file;
        }
        if (is_array($options)) {
            $array = array_merge($array, $options);
        }
        $url = $this->config["url"]["client"]."index.php?".http_build_query($array);
        $url = str_replace("&amp;", "&", $url);
        return $url;
    }
    
    function redirectToParentList() 
    {
        $parentObject = $this->directoryObject->getActualParentObject();
        lw_object::pageReload($this->buildLink('navigation', 'show', $parentObject->getRelativePath()));
    }

    function redirectToActualList() 
    {
        lw_object::pageReload($this->buildLink('navigation', 'show', $this->directoryObject->getRelativePath()));
    }
}