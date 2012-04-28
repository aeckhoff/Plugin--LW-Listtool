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

class ltList extends ltBase
{

    public function __construct()
    {
        parent::__construct();
    }

    function getList()
    {
        $this->response->useJQuery();
        $this->response->useJQueryUI();

        $base = lw_io::loadFile(dirname(__FILE__) . '/../templates/listbase.tpl.html');
        if (filter_var($this->getPluginParameter('template'), FILTER_VALIDATE_INT)) {
            $template = $base . $this->repository->loadTemplateById($this->getPluginParameter('template'));
        }
        else {
            $template = $base . lw_io::loadFile(dirname(__FILE__) . '/../templates/' . $this->getPluginParameter('template'));
        }
        $tpl = new lw_te($template);
        $flag = true;
        if ($this->isWriteAllowed()) {
            $tpl->setIfVar('ltWrite');
            $flag = false;
        }
        if ($this->isReadAllowed()) {
            $tpl->setIfVar('ltRead');
        }
        $entries = $this->repository->loadListByOid($this->getOid(), $this->getPluginParameter('sorting'), $flag);
        $block = $tpl->getBlock("entry");
        if (count($entries) > 0) {
            $tpl->setIfVar('entries');
            foreach ($entries as $entry) {
                if ($oddeven == "even") {
                    $oddeven = "odd";
                }
                else {
                    $oddeven = "even";
                }
                $btpl = new lw_te($block);
                $btpl->reg("oddeven", $oddeven);
                $btpl->reg("id", $entry['id']);
                $btpl->reg("name", $entry['name']);
                $btpl->reg("description", $entry['description']);
                $btpl->reg("addinfo", $entry['opt1text']);
                $btpl->reg("keywords", $entry['opt2text']);
                $btpl->reg("free_date", $entry['opt2number']);
                $btpl->reg("first_date", $entry['lw_first_date']);
                $btpl->reg("last_date", $entry['lw_last_date']);
                $btpl->reg("baseurl", lw_page::getInstance()->getUrl(array("u" => "1")));
                $btpl->reg("cmdIdentifier", $this->cmdIdentifier);
                $btpl->reg("idIdentifier", $this->idIdentifier);
                if ($this->isWriteAllowed()) {
                    $btpl->setIfVar('ltWrite');
                }
                if ($this->isReadAllowed()) {
                    $tpl->setIfVar('ltRead');
                }                
                $btpl->reg("last_username", $entry['last_username']);
                $btpl->reg("published", $entry['published']);
                if ($entry['opt1bool']) {
                    $btpl->setIfVar("link");
                    $btpl->reg("linkurl", $entry['opt3text']);
                    $btpl->reg("opt3text", $entry['opt3text']);
                }
                else {
                    $btpl->setIfVar("file");
                    $btpl->reg("downloadurl", lw_page::getInstance()->getUrl(array($this->cmdIdentifier => "download")));
                    $btpl->reg("filetype", lw_io::getFileExtension($entry['opt2file']));
                    $btpl->reg("thumbnailurl", lw_page::getInstance()->getUrl(array($this->cmdIdentifier => "showThumbnail")));
                    $file = new lw_file($this->config['path']['listtool'], 'item_'.$entry['id'].'.file');
                    $btpl->reg("filesize", $file->getSize());
                    $btpl->reg("upload_date", $file->getDate());
                }
                $out.=$btpl->parse();
            }
            $tpl->putBlock("entry", $out);
        }
        if ($this->getPluginParameter('sorting') == "opt1number") {
            $tpl->setIfVar("manualsorting");
        }
        $tpl->reg("listtitle", $this->getPluginParameter('name'));
        $tpl->reg("addurllink", lw_page::getInstance()->getUrl(array($this->cmdIdentifier => "addEntry", "type" => "link")));
        $tpl->reg("addurlfile", lw_page::getInstance()->getUrl(array($this->cmdIdentifier => "addEntry", "type" => "file")));
        $tpl->reg("sorturl", lw_page::getInstance()->getUrl(array($this->cmdIdentifier => "showSortList")));
        $tpl->reg("baseurl", lw_page::getInstance()->getUrl(array("u" => "1")));
        $tpl->reg("cmdIdentifier", $this->cmdIdentifier);
        $tpl->reg("idIdentifier", $this->idIdentifier);
        $this->output = $tpl->parse();
    }

    function getSortList()
    {
        if ($this->request->getInt("save") == 1) {
            $i = 1;
            $parts = explode(':', $this->request->getRaw("neworder"));
            foreach ($parts as $item) {
                if (strlen(trim($item)) > 0 && intval($item) > 0) {
                    $this->repository->saveSequence(intval($item), $i);
                    $i++;
                }
            }
            $this->reloadParent($array);
        }

        if ($this->auth->isLoggedin())
            $array['admin'] = true;

        $this->response->useJQuery();

        $tpl = new lw_te(lw_io::loadFile(dirname(__FILE__) . '/../templates/sortlist.tpl.html'));
        $tpl->reg('actionurl', lw_page::getInstance()->getUrl(array($this->cmdIdentifier => "showSortList", "save" => 1)));
        if ($this->isWriteAllowed()) {
            $tpl->setIfVar('ltWrite');
        }
        $entries = $this->repository->loadListByOid($this->getOid(), false, false);
        if (count($entries) > 0) {
            $tpl->setIfVar('entries');
            $tpl->regBlock("entry", $entries);
        }
        $this->buildPopup($tpl->parse());
    }

    public function getThumbnail()
    {
        if ($this->isReadAllowedByItemId($this->getEntryId())) {
            include_once(dirname(__FILE__) . '/ltRepository.php');
            $this->repository = new ltRepository();
            $data = $this->repository->loadEntryById($this->getEntryId());
            $file = $this->config['path']['listtool'] . $data['opt1file'];
            if (is_file($file)) {
                header("Content-type: " . lw_io::getMimeType(lw_io::getFileExtension($file)));
                readfile($file);
                exit();
            }
            die("not existing");
        }
        die("not allowed");
    }

    public function getDownload()
    {
        if ($this->isReadAllowedByItemId($this->getEntryId())) {
            include_once(dirname(__FILE__) . '/ltRepository.php');
            $this->repository = new ltRepository();
            $data = $this->repository->loadEntryById($this->getEntryId());
            $file = $this->config['path']['listtool'] . 'item_' . $this->getEntryId() . '.file';
            if (is_file($file)) {
                $extension = lw_io::getFileExtension($data['opt2file']);
                $mimeType = lw_io::getMimeType($extension);
                if (strlen($mimeType) < 1) {
                    $mimeType = "application/octet-stream";
                }
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Content-Type: " . $mimeType);
                header("Content-disposition: attachment; filename=\"" . $data['opt2file'] . "\"");
                readfile($file);
                exit();
            }
            die("not existing");
        }
        die("not allowed");
    }

    public function delete()
    {
        return $this->repository->deleteListByOid($this->getOid());
    }

}
