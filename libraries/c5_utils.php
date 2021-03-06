<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class C5Utils {

    static function addCollectionAttributeKey($akHandle, $akName, $pkg = null) {
        $attributeKey = CollectionAttributeKey::getByHandle($akHandle);

        if (!$attributeKey) {
            $textAttributeType = AttributeType::getByHandle("text");
            $attributeKey = CollectionAttributeKey::add(
                    $textAttributeType, 
                    array("akHandle" => $akHandle, "akName" => $akName), 
                    $pkg);
        }

        return $attributeKey;
        //$idCursoAttributeType 
    }

    static function addPageType($handle, $name, $pkg = null) {
        $pageType = CollectionType::getByHandle($handle);

        if (!$pageType) {
            $data = array("ctHandle" => $handle, "ctName" => $name);
            $pageType = CollectionType::add($data, $pkg);
        }

        return $pageType;
    }

    // install single pages
    static function addSinglePage($name, $path, $description, $pkg = null) {
        $sp = SinglePage::getByPath($path);

        if (!$sp || !intval($sp->getCollectionID())) {
            $sp = SinglePage::add($path, $pkg);
            $sp->update(array("cName" => t($name),
                "cDescription" => t($description)));
        }

        return $sp;
    }

}

?>
