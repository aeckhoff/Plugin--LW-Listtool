<?php

/* * ************************************************************************
 *  Copyright notice
 *
 *  Copyright 1998-2009 Logic Works GmbH
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
 * ************************************************************************* */

class ltBackend extends lw_object {

    public function __construct()
    {
        $this->setRepository();
        $this->request = lw_registry::getInstance()->getEntry("request");
        $this->config = lw_registry::getInstance()->getEntry("config");
    }

    public function deactivate($array) 
    {
        $this->deactivated = $array;
    }
    
    public function isDeactivated($function)
    {
        return in_array($function, $this->deactivated);
    }  
    
    public function setRepository($repository=false) {
        if ($repository) {
            $this->repository = $repository;
        } else {
            include_once(dirname(__FILE__).'/ltRepository.php');
            $this->repository = new ltRepository();
        }
    }    
    
    public function setOid($oid)
    {
        $this->oid = $oid;
    }

    public function getOid()
    {
        return $this->oid;
    }

    public function execute($error=false)
    {
        if ($error) {
            $view = new lw_view($this->config['plugin_path']['lw'] . 'lw_listtool/templates/backenderror.tpl.phtml');
            $view->error = $error;
            $view->mediaurl = $this->config['url']['media'];  
            $view->back = $this->buildUrl(false, array('oid', 'cmd', 'save'));
            $this->output = $view->render();
            return;
        }    
    
        if ($this->request->getAlnum("pcmd") == "assignIntranets") {
            $this->assignIntranets();
            exit();
        }
        if ($this->request->getAlnum("pcmd") == "saveAssignIntranets") {
            $this->saveAssignIntranets();
            exit();
        }
        if ($this->request->getAlnum("pcmd") == "save") {
            $parameter['name'] = $this->request->getRaw("name");
            $parameter['listtooltype'] = $this->request->getRaw("listtooltype");
            $parameter['template'] = $this->request->getRaw("template");
            $parameter['sorting'] = $this->request->getRaw("sorting");
            $parameter['suffix_type'] = $this->request->getRaw("suffix_type");
            $parameter['suffix'] = $this->request->getRaw("suffix");
            $parameter['secured'] = $this->request->getRaw("secured");
            $parameter['language'] = $this->request->getRaw("language");
            if (!$this->isDeactivated('mailMessage')) {
                $parameter['email_messages'] = $this->request->getRaw("email_messages");
                $parameter['email_from'] = $this->request->getRaw("email_from");
                $content = $this->request->getRaw("messagetemplate");
            }
            $this->repository->savePluginData('lw_listtool', $this->getOid(), $parameter, $content);
            $this->pageReload($this->buildURL(array("saved" => 1), array("pcmd")));
        }
        $data = $this->repository->loadPluginData('lw_listtool', $this->getOid());
        $form = $this->_buildAdminForm();
        
        if (!$this->isDeactivated('mailMessage')) {
            $data['parameter']['messagetemplate'] = $data['content'];
        }
        
        $form->setData($data['parameter']);

        $view = new lw_view($this->config['plugin_path']['lw'] . 'lw_listtool/templates/backendform.tpl.phtml');
        $view->form = $form->render();
        $view->mediaurl = $this->config['url']['media'];

        if (method_exists($this, 'getRightsLinks')) {
            $view->rightslink = $this->getRightslinks();
        } 
        else {
            $view->rightslink = '<a href="#" onClick="openNewWindow(\'' . $this->buildUrl(array("pcmd" => "assignIntranets", "ltid" => $this->getOid())) . '\');">Rechtezuweisung</a>';
        }
        
        if (method_exists($this, 'getRightsList')) {
            $view->rightslist = $this->getRightslist();
        } 
        else {
            include_once($this->config['path']['server'] . 'c_backend/intranetassignments/agent_intranetassignments.class.php');
            $assign = new agent_intranetassignments();
            $assign->setObject('listtool_cbox', $this->getOid());
            $view->intranets = $this->repository->getAllAssignedIntranetsByObject('listtool_cbox', $this->getOid());
            $view->users = $this->repository->getAllAssignedUsersByObject('listtool_cbox', $this->getOid());
        }
        $this->output = $view->render();
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function saveAssignIntranets()
    {
        include_once($this->config['path']['server'] . 'c_backend/intranetassignments/agent_intranetassignments.class.php');
        $assign = new agent_intranetassignments();
        $assign->setDelegate($this);
        $assign->setObject('listtool_cbox', $this->getOid());

        $temp = $this->request->getPostArray();
        $assign->setAssignedUsers($temp['user']);
        $assign->setAssignedIntranets($temp['intranet']);
        $assign->saveObject();

        $this->pageReload($this->buildUrl(array("pcmd" => "assignIntranets", "ltid" => $this->getOid())));
    }

    public function assignIntranets()
    {
        include_once($this->config['path']['server'] . 'c_backend/intranetassignments/agent_intranetassignments.class.php');
        $assign = new agent_intranetassignments();

        $assign->setObject('listtool_cbox', $this->getOid());
        $assign->setAction($this->buildUrl(array("pcmd" => "saveAssignIntranets", "ltid" => $this->getOid())));
        $assign->execute();
    }

    protected function _buildAdminForm()
    {
        $form = new lw_fe();
        $form->setRenderer()
                ->setID('lw_listtool')
                ->setIntroduction('Basisdaten der Liste')
    			->addFootnote('<i>via Backenduser</i>: darf nur von Administratoren und Redakteuren bearbeitet werden!<br/><i>via Intranetuser</i>: darf nur von Intranetusern bearbeitet werden!<br/><i>via Backend- und Intranetuser oder ------</i>: darf sowohl von Intranet- als auch Backendusern bearbeitet werden!')
    			->addFootnote('Dateiendungen bitte kommasepariert eingeben und mit f&uuml;hrendem Punkt versehen.<br/>Beispiel: <i>.txt,.doc,.xls</i>')
    			->addFootnote('Whitelist: nur die einegegebenen Dateiendungen sind erlaubt<br/>Blacklist: die einegegebenen Dateiendungen sind <strong>nicht</strong> erlaubt<br/>Wurden keine Dateiendungen eingegeben, sind automatisch alle erlaubt!')
                ->setDefaultErrorMessage('Es sind Fehler aurequestreten!')
                ->setAction($this->buildUrl(array("pcmd" => "save")));

        $sort = array(array("id" => "", "name" => "----"), array("id" => "backend", "name" => "Backenduser"), array("id" => "intranet", "name" => "Intranetuser"), array("id" => "intranet_backend", "name" => "Intranet- und Backenduser"));
        $form->createElement("select")
                ->setName('listtooltype')
                ->setID('lw_listtool_type')
                ->setLabel('Verwaltbar durch')
                ->setValues($sort)
                ->setFootnote('1')
                ->setRequired('Bitte eine Auswahl treffen!')
                ->setFilter('striptags');

        $form->createElement("textfield")
                ->setName('name')
                ->setID('lw_listtool_titel')
                ->setLabel('Titel')
                ->setRequired('Bitte einen Titel angeben!')
                ->setFilter('striptags')
                ->setValidation('hasMaxlength', array('value' => 255), 'Der Wert darf maximal 255 Zeichen lang sein!');

        $templates = $this->repository->getTemplateList();
        $templates[] = array("id" => "list.tpl.html", "name" => "defaultList");
        $templates[] = array("id" => "thumbnaillist.tpl.html", "name" => "defaultThumbnaillist");
        $form->createElement("select")
                ->setName('template')
                ->setID('lw_listtool_template')
                ->setLabel('Vorlage')
                ->setValues($templates)
                ->setFilter('striptags');

        $sort = array(array("id" => "opt1number", "name" => "Sequence (eigene freie Sortierung)"), array("id" => "name", "name" => "Name"), array("id" => "opt1text", "name" => "Zusatzinfo"), array("id" => "opt2number", "name" => "Freies Datum"), array("id" => "opt3number", "name" => "letzter Upload"), array("id" => "lw_last_date", "name" => "letzte Aenderung"), array("id" => "lw_first_date", "name" => "Eintrag angelegt"));
        $form->createElement("select")
                ->setName('sorting')
                ->setID('lw_listtool_sorting')
                ->setLabel('Sortierung')
                ->setValues($sort)
                ->setRequired('Bitte eine Auswahl treffen!')
                ->setFilter('striptags');

        $form->createElement("textfield")
                ->setName('suffix')
                ->setID('lw_listtool_suffix')
                ->setLabel('Dateiendungen')
                ->setFilter('striptags')
                ->setFootnote('2')
                ->setValidation('hasMaxlength', array('value' => 255), 'Der Wert darf maximal 255 Zeichen lang sein!');

        $suffixtypes = array(array("id" => "white", "name" => "Whitelist"), array("id" => "black", "name" => "Blacklist"));
        $form->createElement("select")
                ->setName('suffix_type')
                ->setID('lw_listtool_suffix_type')
                ->setLabel('Suffixtyp')
                ->setFootnote('3')
                ->setValues($suffixtypes)
                ->setRequired('Bitte eine Auswahl treffen!')
                ->setFilter('striptags');

        $languages = array(array("id" => "de", "name" => "deutsch"), array("id" => "en", "name" => "english"));
        $form->createElement("select")
                ->setName('language')
                ->setID('lw_listtool_language')
                ->setLabel('Sprache')
                ->setValues($languages)
                ->setRequired('Bitte eine Auswahl treffen!')
                ->setFilter('striptags');

        if (!$this->isDeactivated('mailMessage')) {
            $email = array(array("id" => false, "name" => "keine"), array("id" => "intranet", "name" => "Intranet Benutzer"));
            $form->createElement("select")
                    ->setName('email_messages')
                    ->setID('lw_listtool_email_messages')
                    ->setLabel('E-Mail Mitteilungen')
                    ->setValues($email)
                    ->setFilter('striptags');

            $form->createElement("textfield")
                    ->setName('email_from')
                    ->setID('lw_listtool_emailfrom')
                    ->setLabel('Absendeadresse')
                    ->setFilter('striptags')
                    ->setValidation('isEmail', false, 'Es muss sich um eine E-Mailadresse handeln!');

            $form->createElement("textarea")
                    ->setName('messagetemplate')
                    ->setID('lw_listtool_messagetemplate')
                    ->setLabel('E-Mail Vorlage')
                    ->setFilter('striptags');
        }
        if (!$this->_feedit) {
            $form->createElement('button')
                    ->setTarget($this->buildUrl(false, array('oid', 'cmd', 'save')))
                    ->setValue('abbrechen');
        }

        $form->createElement('submit')
                ->setValue('speichern');

        return $form;
    }
}
