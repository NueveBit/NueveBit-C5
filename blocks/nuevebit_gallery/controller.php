<?php 
defined('C5_EXECUTE') or die("Access Denied.");
class NuevebitGalleryBlockController extends BlockController {
	
	protected $btTable = 'btNuevebitGallery';
	protected $btInterfaceWidth = "550";
	protected $btInterfaceHeight = "400";
	protected $btCacheBlockRecord = true;
	protected $btCacheBlockOutput = true;
	protected $btCacheBlockOutputOnPost = true;
//	protected $btCacheBlockOutputForRegisteredUsers = true;

	protected $btExportFileColumns = array('fID');
	protected $btExportTables = array('btNuevebitGallery','btNuevebitGalleryImg');

	public $defaultDuration = 5;	
	public $defaultFadeDuration = 2;	
	
	public $playback = "ORDER";	

	/** 
	 * Used for localization. If we want to localize the name/description we have to include this
	 */
	public function getBlockTypeDescription() {
		return t("Nuevebit Image Galleries.");
	}
	
	public function getBlockTypeName() {
		return t("Nuevebit Gallery");
	}

    public function on_page_view() {
        $html = Loader::helper("html");
        $this->addHeaderItem($html->javascript("galleria.js", "nuevebit"));
        $this->addFooterItem($html->javascript("nuevebit.js", "nuevebit"));
    }
	
	public function getJavaScriptStrings() {
		return array(
			'choose-file' => t('Choose Image/File'),
			'choose-min-2' => t('Please choose at least two images.'),
			'choose-fileset' => t('Please choose a file set.')
		);
	}
	
	function getFileSetName(){
		$sql = "SELECT fsName FROM FileSets WHERE fsID=".intval($this->fsID);
		$db = Loader::db();
		return $db->getOne($sql); 
	}

	function loadFileSet(){
		if (intval($this->fsID) < 1) {
			return false;
		}
        Loader::helper('concrete/file');
		Loader::model('file_attributes');
		Loader::library('file/types');
		Loader::model('file_list');
		Loader::model('file_set');
		
		$ak = FileAttributeKey::getByHandle('height');

		$fs = FileSet::getByID($this->fsID);
		$fileList = new FileList();		
		$fileList->filterBySet($fs);
		$fileList->filterByType(FileType::T_IMAGE);	
		$fileList->sortByFileSetDisplayOrder();
		
		$files = $fileList->get(1000,0);
		
		
		$image = array();
		$image['duration'] = $this->duration;
		$image['fadeDuration'] = $this->fadeDuration;
		$image['groupSet'] = 0;
		$image['url'] = '';
		$images = array();
		$maxHeight = 0;
		foreach ($files as $f) {
			$fp = new Permissions($f);
			if(!$fp->canRead()) { continue; }
			$image['fID'] 			= $f->getFileID();
			$image['fileName'] 		= $f->getFileName();
			$image['fullFilePath'] 	= $f->getPath();
			$image['url']			= $f->getRelativePath();
			
			// find the max height of all the images so slideshow doesn't bounce around while rotating
			$vo = $f->getAttributeValueObject($ak);
			if (is_object($vo)) {
				$image['imgHeight'] = $vo->getValue('height');
			}
			if ($maxHeight == 0 || $image['imgHeight'] > $maxHeight) {
				$maxHeight = $image['imgHeight'];
			}
			$images[] = $image;
		}
		$this->images = $images;
	
	}

	function loadImages(){
		if(intval($this->bID)==0) $this->images=array();
		$sortChoices=array('ORDER'=>'position','RANDOM-SET'=>'groupSet asc, position asc','RANDOM'=>'rand()');
		if( !array_key_exists($this->playback,$sortChoices) ) 
			$this->playback='ORDER';
		if(intval($this->bID)==0) return array();
		$sql = "SELECT * FROM btNuevebitGalleryImg WHERE bID=".intval($this->bID).' ORDER BY '.$sortChoices[$this->playback];
		$db = Loader::db();
		$this->images=$db->getAll($sql); 
	}
	
	function delete(){
		$db = Loader::db();
		$db->query("DELETE FROM btNuevebitGalleryImg WHERE bID=".intval($this->bID));		
		parent::delete();
	}
	
	function loadBlockInformation() {
		if ($this->fsID == 0) {
			$this->loadImages();
		} else {
			$this->loadFileSet();
		}
		$this->randomizeImages();	
		$this->set('defaultFadeDuration', $this->defaultFadeDuration);
		$this->set('defaultDuration', $this->defaultDuration);
		$this->set('fadeDuration', $this->fadeDuration);
		$this->set('duration', $this->duration);
		$this->set('minHeight', $this->minHeight);
		$this->set('fsID', $this->fsID);
		$this->set('fsName', $this->getFileSetName());
		$this->set('images', $this->images);
		$this->set('playback', $this->playback);
        $this->set('lazyLoad', $this->lazyLoad);
        $this->set('dataLayer', $this->dataLayer);
		$type = ($this->fsID > 0) ? 'FILESET' : 'CUSTOM';
		$this->set('type', $type);
		$this->set('bID', $this->bID);				
	}
	
	function view() {
		$this->loadBlockInformation();
	}

	function add() {
		$this->loadBlockInformation();
	}
	
	function edit() {
		$this->loadBlockInformation();
	}

    function composer() {
		$this->loadBlockInformation();
    }
	
	function duplicate($nbID) {
		parent::duplicate($nbID);
		$this->loadBlockInformation();
		$db = Loader::db();
		foreach($this->images as $im) {
			$db->Execute('insert into btNuevebitGalleryImg (bID, fID, url, duration, fadeDuration, groupSet, position, imgHeight, title, description) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 
				array($nbID, $im['fID'], $im['url'], $im['duration'], $im['fadeDuration'], $im['groupSet'], $im['position'], $im['imgHeight'], $im["title"], $im["description"])
			);		
		}
	}
	
	function save($data) { 
		$args['playback'] = isset($data['playback']) ? trim($data['playback']) : 'ORDER';
		$db = Loader::db();

		if( $data['type'] == 'FILESET' && $data['fsID'] > 0){
			$args['fsID'] = $data['fsID'];
			$args['duration'] = $data['duration'][0];
			$args['fadeDuration'] = $data['fadeDuration'][0];

			$files = $db->getAll("SELECT fv.fID FROM FileSetFiles fsf, FileVersions fv WHERE fsf.fsID = " . $data['fsID'] .
			         " AND fsf.fID = fv.fID AND fvIsApproved = 1");
			
			//delete existing images
			$db->query("DELETE FROM btNuevebitGalleryImg WHERE bID=".intval($this->bID));
		} else if( $data['type'] == 'CUSTOM' && count($data['imgFIDs']) ){
			$args['fsID'] = 0;

			//delete existing images
			$db->query("DELETE FROM btNuevebitGalleryImg WHERE bID=".intval($this->bID));
			
			//loop through and add the images
			$pos=0;
			foreach($data['imgFIDs'] as $imgFID){ 
				if(intval($imgFID)==0 || $data['fileNames'][$pos]=='tempFilename') continue;

                $title = filter_var($data["title"][$pos], FILTER_SANITIZE_STRING);
                $description = filter_var($data["description"][$pos], FILTER_SANITIZE_STRING);
                
				$vals = array(intval($this->bID),intval($imgFID), $title, $description, trim($data['url'][$pos]),intval($data['duration'][$pos]),intval($data['fadeDuration'][$pos]),
						intval($data['groupSet'][$pos]),intval($data['imgHeight'][$pos]),$pos);
				$db->query("INSERT INTO btNuevebitGalleryImg (bID,fID,title,description,url,duration,fadeDuration,groupSet,imgHeight,position) values (?,?,?,?,?,?,?,?,?,?)",$vals);
				$pos++;
			}
		}
		
		parent::save($args);
	}
	
	function randomizeImages()
	{
		if($this->playback == 'RANDOM')
		{
			shuffle($this->images);
		}
		else if($this->playback == 'RANDOM-SET')
		{
			$imageGroups=array();
			$imageGroupIds=array();
			$sortedImgs=array();
			foreach($this->images as $imgInfo){
				$imageGroups[$imgInfo['groupSet']][]=$imgInfo;
				if( !in_array($imgInfo['groupSet'],$imageGroupIds) )
					$imageGroupIds[]=$imgInfo['groupSet'];
			}
			shuffle($imageGroupIds);
			foreach($imageGroupIds as $imageGroupId){
				foreach($imageGroups[$imageGroupId] as $imgInfo)
					$sortedImgs[]=$imgInfo;
			}
			$this->images=$sortedImgs;
		}
	}
}

?>
