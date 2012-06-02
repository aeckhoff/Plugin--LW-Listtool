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

class ltCRUD extends ltBase
{

    private $language = 'de';

    public function __construct()
    {
        parent::__construct();
    }
    
    function setLanguage($lang)
    {
    	$allowedLanguages = array('de','en');
    	if (in_array($lang,$allowedLanguages)) {
    		$this->language = $lang;
    	} else {
    		$this->language = 'de';
    	}
    }
    
    function addEntry()
    {
        if ($this->isWriteAllowed()) {
            if ($this->request->getInt("save") == 1) {
                $array = $this->_checkAndPrepareValues(true);
                if (!$this->error) {
                    unset($array['shown_opt2number']);
                    $array['lw_first_date'] = date("YmdHis");
                    $array['lw_first_user'] = $this->in_auth->getUserdata("id");
                    $array['lw_last_date'] = date("YmdHis");
                    $array['lw_last_user'] = $this->in_auth->getUserdata("id");
                    $array['category_id'] = $this->getOid();

                    if (!$this->isDeactivated('mailMessage')) {
                        include_once(dirname(__FILE__) . '/ltMailMessage.php');
                        $mailmessage = new ltMailMessage();
                        $mailmessage->setPlugindata($this->plugindata);
                        $mailmessage->sendIntranetMails($array);
                        exit();
                    }
                    
                    $ok = $this->repository->addEntry($array);
                    if (!$this->isDeactivated('mailMessage')) {
                        if ($ok && $this->getPluginParameter('email_messages') == "intranet") {
                            include_once(dirname(__FILE__) . '/ltMailMessage.php');
                            $mailmessage = new ltMailMessage();
                            $mailmessage->sendIntranetMails($array);
                        }
                    }
                    $this->reloadParent($array);
                }
            }

            $this->response->useJQuery();
            $this->response->useJQueryUI();

            $tpl = new lw_te(lw_io::loadFile(dirname(__FILE__) . '/../templates/form.tpl.html'));

            if ($this->error) {
                $parts = explode(':', $this->error);
                foreach ($parts as $error) {
                    if (strlen(trim($error)) > 0) {
                        $tpl->setIfVar('error' . $error);
                    }
                }
            }

            if ($this->isWriteAllowed()) {
                $tpl->setIfVar('ltWrite');
            }
            $tpl->reg('action', lw_page::getInstance()->getUrl(array($this->cmdIdentifier => "addEntry", "save" => 1, "type" => $this->request->getAlnum("type"))));
            if ($array['opt1bool'] == 1 || $this->request->getAlnum('type') == "link") {
                $tpl->setIfVar('link');
            }
            else {
                $tpl->setIfVar('file');
            }
            
            if ($this->getPluginParameter('language') == "en") {
                
                $tpl->setIfVar("en");
            }
            else {
                $tpl->setIfVar("de");
            }
            $this->buildPopup($tpl->parse());
        }
    }

    function editEntry()
    {
        if ($this->isWriteAllowedByItemId($this->getEntryId())) {
            if ($this->request->getInt("save") == 1) {
                $array = $this->_checkAndPrepareValues();
                if (!$this->error) {
                    unset($array['shown_opt2number']);

                    $array['lw_last_date'] = date("YmdHis");
                    $array['lw_last_user'] = $this->in_auth->getUserdata("id");

                    $ok = $this->repository->saveEntryById($this->getEntryId(), $array);
                    $this->reloadParent($array);
                }
            }
            else {
                $array = $this->repository->loadEntryById($this->getEntryId());
            }

            $this->response->useJQuery();
            $this->response->useJQueryUI();

            $tpl = new lw_te(lw_io::loadFile(dirname(__FILE__) . '/../templates/form.tpl.html'));

            if ($this->error) {
                $parts = explode(':', $this->error);
                foreach ($parts as $error) {
                    if (strlen(trim($error)) > 0) {
                        $tpl->setIfVar('error' . $error);
                    }
                }
            }

            if ($this->isWriteAllowed()) {
                $tpl->setIfVar('ltWrite');
            }
            $tpl->reg('action', lw_page::getInstance()->getUrl(array($this->idIdentifier => $this->getEntryId(), $this->cmdIdentifier => "editEntry", "save" => 1)));
            $tpl->reg('type', $this->request->getRaw("type"));
            if ($array['opt1bool'] == 1 || $this->request->getAlnum('type') == "link") {
                $tpl->setIfVar('link');
            }
            else {
                $tpl->setIfVar('file');
            }
            $tpl->regArray($array);

            if ($array['published'] == 1) {
                $tpl->setIfVar("published_checked");
            }

            //$dir = lw_directory::getInstance($this->config['path']['listtool']);

            if ($array['opt1file']) {
                $tpl->setIfVar("opt1file");
                $tpl->reg('opt1file_name', $array['opt1file']);
                $tpl->reg('opt1file_thumbnail', lw_page::getInstance()->getUrl(array($this->cmdIdentifier => "showThumbnail", $this->idIdentifier => $this->getEntryId())));
                $tpl->reg('opt1file_path', $this->config['url']['listtool']);
            }

            if ($array['opt2file']) {
                $tpl->setIfVar("fileexists");
                $file = new lw_file($this->config['path']['listtool'], 'item_' . $this->getEntryId() . '.file');
                $tpl->reg('opt2file_name', $array['opt2file']);
                $tpl->reg('opt2file_path', $this->config['url']['listtool']);
                $tpl->reg('opt2file_size', $file->getSize());
                $tpl->reg('opt2file_rights', $file->getRights());
            }

            if ($this->getPluginParameter('language') == "en") {
                
                $tpl->setIfVar("en");
            }
            else {
                $tpl->setIfVar("de");
            }

            $this->buildPopup($tpl->parse());
        }
    }

    private function _checkAndPrepareValues($newentry=false)
    {
        if (!$newentry) {
            $olddata = $this->repository->loadEntryById($this->getEntryId());
        }

        $this->error = false;
        $array = $this->request->getPostArray();

        if (strlen(trim($array['name'])) < 1) {
            $this->error.=":1:";
        }
        if (strlen(trim($array['name'])) > 255) {
            $this->error.=":2:";
        }
        if (strlen(trim($array['description'])) > 255) {
            $this->error.=":3:";
        }
        if (!$array['opt2number'] && strlen(trim($array['shown_opt2number']))>8) {
            $parts = explode(".", $array['shown_opt2number']);
            foreach($parts as $part) {
                $day = substr(str_pad(intval($part[0]), 2, "0", STR_PAD_LEFT), 0, 2);
                $month = substr(str_pad(intval($part[1]), 2, "0", STR_PAD_LEFT), 0, 2);
                $year = substr(intval($part[2]), 0, 4);
                $array['opt2number'] = $year.$month.$day;
            }
        }
        $array['opt2number'] = intval($array['opt2number']);        
        if (strlen(trim($array['opt2number'])) > 8) {
            $this->error.=":4:";
        }
        if (strlen(trim($array['opt3text'])) > 255) {
            $this->error.=":5:";
        }
        if (strlen(trim($array['opt1text'])) > 255) {
            $this->error.=":6:";
        }
        if (strlen(trim($array['opt2text'])) > 255) {
            $this->error.=":7:";
        }

        if ($array['opt1bool'] != 1) {
            $array['opt1bool'] = 0;
        }

        if ($this->request->getRaw("type") == "link" || $olddata['opt1bool'] == 1) {
            $array['opt1bool'] = 1;
        }
        else {
            $array['opt1bool'] = 0;
        }

        if ($array['published'] < 1) {
            $array['published'] = 0;
        }

        if ($array['opt1bool'] == 1) {
            $check = str_replace("http://", "", $array['opt3text']);
            $check = str_replace("https://", "", $check);
            if (strlen(trim($check)) < 1) {
                $this->error.=":8:";
            }
            if (substr($array['opt3text'], 0, 7) != "http://" && substr($array['opt3text'], 0, 8) != "https://") {
                $array['opt3text'] = "http://" . $array['opt3text'];
            }
        }

        $array = $this->_checkThumbnail($array);
        $array = $this->_checkFile($array);

        return $array;
    }

    private function _checkThumbnail($array)
    {
        $thumbnail = $this->request->getFileData('opt1file');
        if ($thumbnail['tmp_name'] && $thumbnail['size'] > 0) {
            $array['opt1file_tmp'] = $thumbnail['tmp_name'];
            $array['opt1file_name'] = $thumbnail['name'];
            $ext = lw_io::getFileExtension($thumbnail['name']);
            if ($ext != 'jpg' && $ext != 'jpeg' && $ext != 'gif' && $ext != 'png') {
                $this->error.=":9:";
                $array['opt1file_allowed'] = "jpg,jpeg,gif,png";
                $this->error = true;
            }
        }
        return $array;
    }

    private function _checkFile($array)
    {
        $file = $this->request->getFileData('opt2file');
        if ($file['tmp_name'] && $file['size'] > 0) {
            $array['opt2file_tmp'] = $file['tmp_name'];
            $array['opt2file_name'] = $file['name'];
            $ext = lw_io::getFileExtension($file['name']);
            $ext = str_replace(".", "", $ext);
            if ($this->getPluginParameter('suffix')) {
                $extis = explode(",", $this->getPluginParameter('suffix'));
                foreach($extis as $singleext) {
                    $exts[] = trim($singleext);
                }
                if (in_array('.' . $ext, $exts) && $this->getPluginParameter('suffix_type') == "black") {
                    $array = $this->_setSuffixError($array, true);
                }
                elseif (!in_array('.' . $ext, $exts) && $this->getPluginParameter('suffix_type') == "white") {
                    $array = $this->_setSuffixError($array);
                }
            }
        }
        elseif ($newentry == true && $this->request->getAlnum('type') != "link") {
            $this->error.=":11:";
        }
        return $array;
    }

    private function _setSuffixError($array, $black=false)
    {
        $this->error.=":10:";
        if ($black) {
            $array['opt2file_notallowed'] = $this->getPluginParameter('suffix');
        }
        else {
            $array['opt2file_allowed'] = $this->getPluginParameter('suffix');
        }
        return $array;
    }

    function deleteEntry()
    {
        if ($this->isWriteAllowedByItemId($this->getEntryId())) {
            $ok = $this->repository->deleteEntryById($this->getEntryId());
            return "showList";
        }
    }

}
