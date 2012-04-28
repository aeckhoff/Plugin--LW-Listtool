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

class TemplateDAO {

    public function __construct(lw_db $db)
    {
        $this->db = $db;
    }

    public function getTemplateList()
    {
        $sql = "SELECT pages.id, pages.name FROM ".$this->db->gt('lw_pagemeta')." pagemeta, ".$this->db->gt('lw_pages')." pages WHERE pagemeta.keywords like '%lw_listtool%' AND pagemeta.id = pages.id";
        return $this->db->select($sql);
    }
    
    public function loadTemplateById($id)
    {
        $showPage = new agent_showpage();
        $template = $showPage->getPageView($id, true);
        $template = html_entity_decode($template, ENT_QUOTES);
        $template = str_replace('&apos;', "'", $template);        
        return $template;
    }
}
