<?php

define('DIRNAME_LESS', 'less');

class AssetsHelper extends HtmlHelper {

    private $headerJsItems = array();

    public function javascript($file, $pkgHandle = null, $includeInHeader = false) {
        $outputObject = parent::javascript($file, $pkgHandle);
        
        if ($includeInHeader) {
            $this->headerJsItems[] = $outputObject;
        }

        return $outputObject;
    }

    public function getHeaderJsItems() {
        return $this->headerJsItems;
    }

    public function less($file, $pkgHandle = null) {
		$less = new LessOutputObject();

		// if the first character is a / then that means we just go right through, it's a direct path
		if (substr($file, 0, 1) == '/' || substr($file, 0, 4) == 'http' || strpos($file, DISPATCHER_FILENAME) > -1) {
			$less->compress = false;
			$less->file = $file;
		}
		
		$v = View::getInstance();
		// checking the theme directory for it. It's just in the root.
		if ($v->getThemeDirectory() != '' && file_exists($v->getThemeDirectory() . '/' . $file)) {
			$less->file = $v->getThemePath() . '/' . $file;
		} else if (file_exists(DIR_BASE . '/' . DIRNAME_LESS . '/' . $file)) {
			$less->file = DIR_REL . '/' . DIRNAME_LESS . '/' . $file;
		} else if ($pkgHandle != null) {
			if (file_exists(DIR_BASE . '/' . DIRNAME_PACKAGES . '/' . $pkgHandle . '/' . DIRNAME_LESS . '/' . $file)) {
				$less->file = DIR_REL . '/' . DIRNAME_PACKAGES . '/' . $pkgHandle . '/' . DIRNAME_LESS . '/' . $file;
			} else if (file_exists(DIR_BASE_CORE . '/' . DIRNAME_PACKAGES . '/' . $pkgHandle . '/' . DIRNAME_LESS . '/' . $file)) {
				$less->file = ASSETS_URL . '/' . DIRNAME_PACKAGES . '/' . $pkgHandle . '/' . DIRNAME_LESS . '/' . $file;
			}
		}
			
		if ($less->file == '') {
            $less->file .= (strpos($less->file, '?') > -1) ? '&amp;' : '?';
            $less->file .= 'v=' . md5(APP_VERSION . PASSWORD_SALT);		
            // for the javascript addHeaderItem we need to have a full href available
            $less->href = $less->file;
            if (substr($less->file, 0, 4) != 'http') {
                $less->href = BASE_URL . $less->file;
            }
		}

		return $less;
    }
}

class LessOutputObject extends CSSOutputObject {

	public function __toString() {
		return '<link rel="stylesheet" type="text/less" href="' . $this->file . '" />';
	}
	
}

?>