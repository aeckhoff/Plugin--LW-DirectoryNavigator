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

class backend extends projectBasis
{
    function __construct() 
    {
        require_once dirname(__FILE__).'/projectBasis.php';
    }
    
    function backend_save()
    {
        $parameter['homedir'] = $this->request->getRaw("homedir");
        $parameter['allowedExtensions'] = $this->request->getRaw("allowedExtensions");
        $parameter['maxDirLevels'] = $this->request->getInt("maxDirLevels");
        $parameter['use_custom_css'] = $this->request->getInt("use_custom_css");
        $parameter['use_only_lwdirinfo_homedir'] = $this->request->getInt("use_only_lwdirinfo_homedir");
        $parameter['treeView'] = $this->request->getInt("treeView");
        $parameter['showbreadcrumb'] = $this->request->getInt("showbreadcrumb");
        
        $content = false;
        $this->repository->plugins()->savePluginData($this->getPluginName(), $this->getOid(), $parameter, $content);
        $this->pageReload($this->buildURL(false, array("pcmd")));
    }
    
    function backend_view()
    {
        $data = $this->repository->plugins()->loadPluginData($this->getPluginName(), $this->getOid());
        $form = $this->_buildAdminForm();
        $form->setData($data['parameter']);
        $tpl = new lw_te(file_get_contents(dirname(__FILE__) . '/../templates/backendform.tpl.html'));
        $tpl->reg("form", $form->render());
        return $tpl->parse();
    }
    
    function _buildAdminForm() 
    {
        $form = new lw_fe();
        $form->setRenderer()
                ->setID('lw_listtool')
                ->setIntroduction('Basisdaten der Liste')
                ->setDefaultErrorMessage('Es sind Fehler aufgetreten!')
                ->setAction($this->buildUrl(array("pcmd"=>"save")));
        
        $form->createElement("textfield")
                ->setName('homedir')
                ->setID('lw_homedir')
                ->setLabel('Startverzeichnis');
        
        $form->createElement("textfield")
                ->setName('allowedExtensions')
                ->setID('lw_allowedExtensions')
                ->setLabel('Erlaubte Datei-Endungen');
        
        $form->createElement("textfield")
                ->setName('maxDirLevels')
                ->setID('lw_maxDirLevels')
                ->setLabel('Max. Verzeichnistiefe (nach Home)');

        $form->createElement("checkbox")
                ->setName('use_custom_css')
                ->setID('lw_use_custom_css')
                ->setLabel('Benutzerdefiniertes CSS?')
                ->setValue(1)
                ->setFilter('striptags')
                ->setValidation('hasMaxlength', array('value' => 1), 'Der Wert darf maximal 1 Zeichen lang sein!');

        $form->createElement("checkbox")
                ->setName('use_only_lwdirinfo_homedir')
                ->setID('lw_use_only_lwdirinfo_homedir')
                ->setLabel('Nur die "lwdirinfo" vom Startverzeichnis verwenden?')
                ->setValue(1)
                ->setFilter('striptags')
                ->setValidation('hasMaxlength', array('value' => 1), 'Der Wert darf maximal 1 Zeichen lang sein!');
        
        $form->createElement("checkbox")
                ->setName('treeView')
                ->setID('lw_treeView')
                ->setLabel('Baumstruktur anzeigen ?')
                ->setValue(1)
                ->setFilter('striptags')
                ->setValidation('hasMaxlength', array('value' => 1), 'Der Wert darf maximal 1 Zeichen lang sein!');
        
        $form->createElement("checkbox")
                ->setName('showbreadcrumb')
                ->setID('lw_showbreadcrumb')
                ->setLabel('Breadcrumb anzeigen ?')
                ->setValue(1)
                ->setFilter('striptags')
                ->setValidation('hasMaxlength', array('value' => 1), 'Der Wert darf maximal 1 Zeichen lang sein!');
        
        $form->createElement('button')
                ->setTarget('admin.php')
                ->setValue('abbrechen');

        $form->createElement('submit')
                ->setValue('speichern');

        return $form;
    }
}
