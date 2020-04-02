<?php

namespace TAS\Core;

class DocumentFile extends \TAS\Core\UserFile
{
    public $LinkerType = '';
    
    public function __construct($linkertype = '')
    {
        $this->init();
        if ($linkertype != '') {
            $this->LinkerType = $linkertype;
        }
    }
    
    private function init()
    {
        parent::__construct();
        $this->FileType = 'document';
        $this->LinkerType = 'products';
        $GLOBALS['tables']['documents'] = 'document';
    }
    
    public function Validate($file = '')
    {
        return true;
    }
    
    /**
     * Function To Upload File from $_FILES replicated Array. Also Save files if second parameter is true.
     */
    public function Upload($file, $save = true, $linkerid = 0)
    {
        $returnfile = array();
        if (!is_array($file)) {
            return false;
        }
        foreach ($file as $key => $filedata) {
            if (!is_array($filedata)) {
                continue;
            }
            //Load file to given path
            //Before that find the location
            if ($this->FindPathForNew($GLOBALS['Tables']['document'])) {
                $fileext = explode('.', $filedata['name']);
                $fileext = strtolower($fileext[count($fileext) - 1]);
                if ($this->Validate($filedata)) {
                    //Create a Random file name
                    $filename = $this->getFileName('.'.$fileext);
                    if (move_uploaded_file($filedata['tmp_name'], $this->FullPath.'/'.$filename)) {
                        if ($save) {
                            $idpart = $this->Save($filename, $filedata, $linkerid);
                            if(!is_bool($idpart) && (int)$idpart > 0)
                            {
                                $filedata['UploadStatus'] = true;
                                $filedata['ID'] = $idpart;
                            }
                            else
                            {
                                $this->SetError('Fail to save in database (File :'.$filedata['name'].')');
                                $filedata['UploadStatus'] = false;
                            }
                        } else {
                            $filedata['UploadStatus'] = true;
                        }
                    } else {
                        $filedata['UploadStatus'] = false;
                        $this->SetError('Unable to save '.$filedata['name']);
                        continue;
                    }
                } else {
                    $filedata['UploadStatus'] = false;
                    $this->SetError($filedata['name'].' fails to validate security check');
                    continue;
                }
            } else {
                $filedata['UploadStatus'] = false;
                continue;
            }
            $returnfile[$key] = $filedata;
        }
        
        return $returnfile;
    }
    
    public function Save($file, $filedata, $linkerid = '')
    {
        $InsertData['documentcaption'] = $filedata['caption'];
        $InsertData['filepath'] = $file;
        $InsertData['linkerid'] = $linkerid;
        $InsertData['linkertype'] = $this->LinkerType;
        $InsertData['status'] = $filedata['status'];
        $InsertData['size'] = $filedata['size'];
        $InsertData['originalname'] = $filedata['name'];
        $InsertData['updatedate'] = date('Y-m-d H:i:s');
        $InsertData['isdefault'] = (isset($filedata['isdefault']) ? $filedata['isdefault'] : 0);
        if (isset($filedata['recordid']) && $filedata['recordid'] > 0) {
            if ($GLOBALS['db']->Update($GLOBALS['tables']['documents'], $InsertData, $filedata['recordid'], 'documentid')) {
                return (int)$filedata['recordid'];
            } else {
                return false;
            }
        } else {
            $InsertData['adddate'] = date('Y-m-d H:i:s');
            if ($GLOBALS['db']->Insert($GLOBALS['tables']['documents'], $InsertData)) {
                $documentid = $GLOBALS['db']->GeneratedID();
                return (int)$documentid;
            } else {
                return false;
            }
        }
    }
    
    public function GetDocumentOnLinker($linkerid)
    {
        $document = array();
        //echo "Select * from ".$GLOBALS['tables']['documents']." where linkertype='".$this->LinkerType."' and linkerid=$linkerid";
        $documentlist = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['tables']['documents'].
            " where linkertype='".$this->LinkerType."' and linkerid=$linkerid order by adddate desc");
        if ($GLOBALS['db']->RowCount($documentlist) > 0) {
            while ($rowdocument = $GLOBALS['db']->FetchArray($documentlist)) {
                $folder = $this->FindFolder($rowdocument['documentid']);
                $document[$rowdocument['documentid']]['filename'] = $rowdocument['filepath'];
                $document[$rowdocument['documentid']]['caption'] = $rowdocument['documentcaption'];
                $document[$rowdocument['documentid']]['url'] = $this->BaseUrl."/$folder/".$rowdocument['filepath'];
                $document[$rowdocument['documentid']]['physicalpath'] = $this->Path."/$folder/".$rowdocument['filepath'];
                $document[$rowdocument['documentid']]['isdefault'] = $rowdocument['isdefault'];
                $document[$rowdocument['documentid']]['adddate'] = $rowdocument['adddate'];
                $document[$rowdocument['documentid']]['updatedate'] = $rowdocument['updatedate'];
                $document[$rowdocument['documentid']]['status'] = $rowdocument['status'];
                $document[$rowdocument['documentid']]['size'] = $rowdocument['size'];
                $document[$rowdocument['documentid']]['name'] = $rowdocument['originalname'];
            }
        }
        
        return $document;
    }
    
    /**
     * Return the document information.
     *
     * @param string $docid
     */
    public function GetDocument($docid)
    {
        $document = array();
        // echo "Select * from ".$GLOBALS['tables']['documents']." where linkertype='".$this->LinkerType."' and linkerid=$linkerid";
        $documentlist = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['Tables']['document']." where documentid='".$docid."' limit 1");
        if ($GLOBALS['db']->RowCount($documentlist) > 0) {
            while ($rowdocument = $GLOBALS['db']->FetchArray($documentlist)) {
                $folder = $this->FindFolder($rowdocument['documentid']);
                $document[$rowdocument['documentid']]['filename'] = $rowdocument['filepath'];
                $document[$rowdocument['documentid']]['caption'] = $rowdocument['documentcaption'];
                $document[$rowdocument['documentid']]['url'] = $this->BaseUrl."/$folder/".$rowdocument['filepath'];
                $document[$rowdocument['documentid']]['physicalpath'] = $this->Path."/$folder/".$rowdocument['filepath'];
                $document[$rowdocument['documentid']]['isdefault'] = $rowdocument['isdefault'];
                $document[$rowdocument['documentid']]['adddate'] = $rowdocument['adddate'];
                $document[$rowdocument['documentid']]['updatedate'] = $rowdocument['updatedate'];
                $document[$rowdocument['documentid']]['status'] = $rowdocument['status'];
                $document[$rowdocument['documentid']]['size'] = $rowdocument['size'];
                $document[$rowdocument['documentid']]['name'] = $rowdocument['originalname'];
            }
        }
        
        return $document;
    }
    
    public static function DownloadURL($document)
    {
        return $GLOBALS['AppConfig']['HomeURL'].'/document/'.$document['id'].'/'.$document['name'];
    }
    
    //Function to delete document on Linker
    public function DeleteDocumentOnLinker($linkerid)
    {
        $document = array();
        $documentlist = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['tables']['documents']." where linkertype='".$this->LinkerType."' and linkerid=$linkerid");
        //Remove Physical File
        if ($GLOBALS['db']->RowCount($documentlist) > 0) {
            while ($rowdocument = $GLOBALS['db']->FetchArray($documentlist)) {
                $folder = $this->FindFolder($rowdocument['documentid']);
                @unlink($this->Path."/$folder/".$rowdocument['filepath']);
            }
        }
        //Clean From DB
        $GLOBALS['db']->Execute('Delete from '.$GLOBALS['tables']['documents']." where linkertype='".$this->LinkerType."' and linkerid=$linkerid");
    }
    
    //Function to delete document on Linker
    public function DeleteDocument($documentid)
    {
        $document = array();
        $documentlist = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['tables']['documents']." where documentid=$documentid");
        //Remove Physical File
        if ($GLOBALS['db']->RowCount($documentlist) > 0) {
            while ($rowdocument = $GLOBALS['db']->FetchArray($documentlist)) {
                $folder = $this->FindFolder($rowdocument['documentid']);
                if (file_exists($this->Path."/$folder/".$rowdocument['filepath'])) {
                    unlink($this->Path."/$folder/".$rowdocument['filepath']);
                }
            }
        }
        //Clean From DB
        return $GLOBALS['db']->Execute('Delete from '.$GLOBALS['tables']['documents']." where documentid=$documentid");
    }
    
    public function SetFileCaption($documentid, $newCaption)
    {
        if (!empty($documentid) && $documentid > 0 && $newCaption != '') {
            $documentlist = $GLOBALS['db']->Execute('Select * from '.$GLOBALS['tables']['documents']." where documentid=$documentid");
            if ($GLOBALS['db']->RowCount($documentlist) > 0) {
                $GLOBALS['db']->Execute('update '.$GLOBALS['tables']['documents']." set documentcaption='".$newCaption."' where documentid=$documentid");
                
                return true;
            } else {
                $this->SetError('Invalid data to change File caption');
                
                return false;
            }
        } else {
            $this->SetError('Invalid data to change File caption');
        }
    }
    
    public static function DocumentForm($actionURL, $formtitle, $documentid = 0)
    {
        if ($documentid > 0) {
            $D = $GLOBALS['documentfile']->GetDocument($documentid);
            if (count($D) > 0) {
                $firstid = key($D);
                $firstDocument = current($D);
                $D = $documentObj['caption'];
            } else {
                $D = '';
            }
        } else {
            $D = '';
        }
        
        $form = '<div class="col-md-12 pt-3"> <div class="card card-body card-radius">
<h2 class="borderbottom-set">'.$formtitle.'</h2>
<form action="'.$actionURL.'" method="post" class="validate" enctype="multipart/form-data"  novalidate="novalidate">
	<fieldset class="generalform">
		<legend></legend>
		<div class="formfield">
			<label for="title" class="formlabel requiredfield">Caption</label>
			<div class="forminputwrapper">
				<input type="text" name="title" id="title" size="32" maxlength="75" class="form-control required" value="'.$D.'" />
			</div>
		<div class="clear"></div></div>
				    
		<div class="formfield">
			<label for="document" class="formlabel requiredfield">Document</label>
			<div class="forminputwrapper">
				<input type="file" name="document" id="document" class="form-control required" aria-required="true" />
			</div>
		<div class="clear"></div></div>
				    
		<div class="formbutton">
			<input name="btnsubmit" id="btnsubmit" class="btn primary-color primary-bg-color py-2" value="Submit" type="submit">
		</div>
	</fieldset>
	</form></div></div></div>';
        
        return $form;
    }
    
    public static function DocumentGrid($linkertype = 'cms', $filters = array(), $parameters = array())
    {
        $SQLQuery['basicquery'] = 'select * from '.$GLOBALS['Tables']['document'];
        $filter = array();
        $filter[] = " linkertype='".$linkertype."'";
        
        if (is_array($filters) && count($filters) > 0) {
            $filter = array_merge($filter, $filters);
        }
        
        if (count($filter) > 0) {
            $SQLQuery['where'] = ' where '.implode(' and ', $filter).' ';
        } else {
            $SQLQuery['where'] = '';
        }
        
        $pages['gridpage'] = $parameters['gridpage']; //$GLOBALS['AppConfig'] ['AdminURL'] . '/docmanager/index.php';
        $pages['edit'] = false;
        $pages['delete'] = $parameters['delete'];  //$GLOBALS['AppConfig'] ['AdminURL'] . '/docmanager/index.php';
        $param['defaultorder'] = 'documentcaption';
        $param['defaultsort'] = 'asc';
        $param['indexfield'] = 'documentid';
        $param['tablename'] = $GLOBALS['Tables']['document'];
        $param['fields'] = array(
            'documentcaption' => array(
                'type' => 'string',
                'name' => 'Name',
            ),
        );
        $param['allowselection'] = false;
        $param['LinkFirstColumn'] = false;
        $param['MultiTableSearch'] = false;
        
        $extraIcons = array();
        
        $extraIcons[0]['link'] = $parameters['getcode']; //$GLOBALS['AppConfig'] ['AdminURL'] . '/docmanager/getcode.php';
        $extraIcons[0]['iconclass'] = 'fa-external-link-alt';
        $extraIcons[0]['tooltip'] = 'Get Document URL';
        $extraIcons[0]['tagname'] = 'documentcode colorboxpopup';
        $extraIcons[0]['paramname'] = 'documentid';
        $param['extraicons'] = $extraIcons;
        $listing = \TAS\Core\UI::HTMLGridFromRecordSet($SQLQuery, $pages, 'docmanager', $param);
        
        $GLOBALS['pageParse']['MetaExtra'] .= '<script type="text/javascript">$(function(){
    		$(".documentcode").colorbox({iframe: true, width:"80%", height:"80%"});
    	});</script>';
        
        return $listing;
    }
}
