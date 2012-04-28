<?php

class ltMailMessage extends ltBase 
{
    function __construct()
    {
        parent::__construct();
    }

    function sendIntranetMails($array) 
    {
        $this->_prepareMailTemplate();
        $mail_addresses = $this->_getMailAddresses();
        if (count($mail_addresses)>0) {
            $message = $this->_generateMessageFromData($array);
            $subject = $this->_generateSubjectFromData($array);

            echo "***************Subject*****************\n<br/>";
            echo $subject."\n<br/><br/>";
            echo "***************Message*****************\n<br/>";
            echo nl2br($message)."\n<br/><br/>";
            echo "***************Mailto*****************<ul>\n<br/>";

            foreach($mail_addresses as $address => $value) {
                if ($value == true) {
                    //$ok = $this->_sendMail($address, $subject, $message);
                    echo "<li>".$address."</li>\n";
                }
            }
            echo "</ul>\n";
            exit();
            return $ok;
        }
    }    
    
    function _getMailAddresses() 
    {
        $intrarep = lw_registry::getInstance()->getEntry('repository')->getRepository('intranetadmin');
        $erg = $intrarep->getAllAssignmentsByObject('page', lw_page::getInstance()->getPageValue('id'));
        foreach($erg as $entry) {
            if ($entry['right_type'] == "intranet") {
                $this->db->setStatement("SELECT email FROM t:lw_in_user WHERE intranet_id = :id");
                $this->db->bindParameter('id',    'i', 	$iid);
                $args = $this->db->pselect();
                foreach($args as $ergs) {
                    if (strlen(trim($ergs['email']))>0) {
                        $mails[$ergs['email']] = true;
                    }
                }
            }
            elseif ($entry['right_type'] == "user") {
                if (strlen(trim($userids))>0) $userids.=" OR ";
                $userids = $userids." id = ".$entry['right_id'];
            }
        }
        $sql = "SELECT email FROM ".$this->db->gt("lw_in_user")." WHERE ".$userids;
        $args = $this->db->select($sql);
        foreach($args as $ergs) {
            if (strlen(trim($ergs['email']))>0) {
                $mails[$ergs['email']] = true;
            }
        }
        return $mails;
    }
    
    function _generateMessageFromData($array) {
        $tpl = new lw_te($this->tpl['message']);
        $tpl->setTags("##-", "-##");
        if (strlen(trim($array['opt2file_name']))>0) {
            $tpl->setIfVar("file");
        }
        else {
            $tpl->setIfVar("link");
        }
        $tpl->reg("name", $array['name']);
        $tpl->reg("freedate", $array['opt2number']);
        $tpl->reg("file", $array['opt2file_name']);
        $tpl->reg("link", $array['opt3text']);
        return $tpl->parse();
    }
    
    function _generateSubjectFromData($array) {
        $tpl = new lw_te($this->tpl['subject']);
        $tpl->setTags("##-", "-##");
        if (strlen(trim($array['opt2file_name']))>0) {
            $tpl->setIfVar("file");
        }
        else {
            $tpl->setIfVar("link");
        }
        return $tpl->parse();
    }
    
    function _sendMail($address, $subject, $message) {
        
    }
    
    function _prepareMailTemplate() {
        if (strlen(trim($this->plugindata['content']))>0) {
            $template = $this->plugindata['content'];
        }
        else {
            $template = "##- lw:blockstart subject -##";
            $template.= "##- lw:if file -##";
            $template.= "A new document was uploaded!\n";
            $template.= "##- lw:endif file -##";
            $template.= "##- lw:if file -##";
            $template.= "A new link was added!\n";
            $template.= "##- lw:endif file -##";
            $template.= "##- lw:blockend subject -##";
            $template.= "\n";
            $template.= "##- lw:blockstart message -##";
            $template.= "Dear Member,\n";
            $template.= "\n";
            $template.= "##- lw:if file -##";
            $template.= "a new document ('##- lw:var name -##') was uploaded to the Intranet!\n";
            $template.= "##- lw:endif file -##";
            $template.= "##- lw:if file -##";
            $template.= "a new link ('##- lw:var name -##') was added to the Intranet!\n";
            $template.= "##- lw:endif file -##";
            $template.= "\n";
            $template.= "best regards,\n";
            $template.= "Your Website Team\n";
            $template.= "##- lw:blockend message -##";
        }
        $tpl = new lw_te($template);
        $tpl->setTags("##-", "-##");
        $this->tpl['subject'] = $tpl->getBlock("subject");
        $this->tpl['message'] = $tpl->getBlock("message");
    }
}