<?php
require($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
require 'MyParser.php';
$instance=new MyParser(10, $USER->IsAdmin());  //передаем ид инфоблока
$instance->deleteElements();  // юзаем этот метод если нужно удалить старые элементы в инфоблоке
$instance->addAllProp();       //этот метод добавляет в массив $arProps все свойства с типом список и варианты этих свойств
$instance->loadElementsFromFile("personal.csv", $USER->GetID());  //метод для добавления элементов в инфоблок через файл


