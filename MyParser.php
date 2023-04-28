<?php

class MyParser
{
    public int $IBLOCK_ID;
    public int $row;
    public object $el;
    public array $arProps;
    public array $PROP;

    public function __construct(int $IBLOCK_ID, bool $userAdmin, int $row = 1)
    {
        $this->IBLOCK_ID = $IBLOCK_ID;
        $this->row = $row;
        //ПОДКЛЮЧЕНИЕ
        if (!$userAdmin) {      //если пользователь не администратор перенаправим его на главную
            LocalRedirect('/');
        }
        CModule::IncludeModule("iblock");
        $this->el = new CIBlockElement;
    }

    public function deleteElements()
    {
        $rsElements = CIBlockElement::GetList([], ['IBLOCK_ID' => $this->IBLOCK_ID], false, false, ['ID']);
        while ($element = $rsElements->GetNext()) {
            CIBlockElement::Delete($element['ID']);        //удаляем старые элементы инфоблока
        }
    }

    public function addAllProp()
    {
        $rsProp = CIBlockPropertyEnum::GetList(
            ["SORT" => "ASC", "VALUE" => "ASC"],
            ['IBLOCK_ID' => $this->IBLOCK_ID]     //второй массив фильтр, т.е. фильтруем только по ид инфоблока, т.е. мы получаем все варианты всех св-в этого инфоблока
        );
        while ($arProp = $rsProp->Fetch()) {
            $key = trim($arProp['VALUE']);         //тупо перебираем все варианты значений подряд
            $this->arProps[$arProp['PROPERTY_CODE']][$key] = $arProp['ID'];
        }
    }

    public function loadElementsFromFile(string $fileName, int $userId)
    {
        if (($handle = fopen($fileName, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if ($this->row == 1) {
                    $this->row++;
                    continue;
                }
                $this->row++;

                $this->PROP['NAME'] = $data[1];
                $this->PROP['PROFESSION'] = $data[2];          //заполняем массив: "код св-ва инфоблока" => "значение ячейки в CSV"
                $this->PROP['SEX'] = $data[3];
                $this->PROP['MAIL'] = $data[4];
                $this->PROP['EMPLOYMENT'] = $data[5];
                $this->PROP['SCHEDULE'] = $data[6];

                foreach ($this->PROP as $key => &$value) { //перебираем св-ва
                    $value = trim($value);
                    $value = str_replace('\n', '', $value);
                    if ($this->arProps[$key]) {//если это список, т.е. если есть такой элемент в массиве св-в с типом список
                        $arSimilar = [];
                        foreach ($this->arProps[$key] as $propKey => $propVal) { //перебираем варианты
                            if (stripos($propKey, $value) !== false) {  //если совпало с вариантом
                                $value = $propVal;        //при чем редактируется именно массив $PROP, т.к. $value по ссылке
                                break;                            //т.е. если текст совпадает мы кладем в $value ид варианта (и соответсвено это добавляется в PROP)
                            }
                            if (similar_text($propKey, $value) > 50) {
                                $value = $propVal;
                            }
                        }
                    }
                }
                $arLoadProductArray = [
                    "MODIFIED_BY" => $userId,
                    "IBLOCK_SECTION_ID" => false,           //если false то элемент добавляется в корень инфоблока
                    "IBLOCK_ID" => $this->IBLOCK_ID,
                    "PROPERTY_VALUES" => $this->PROP,
                    "NAME" => $data[1],
                    "ACTIVE" => 'Y'
                ];

                if ($PRODUCT_ID = $this->el->Add($arLoadProductArray)) {        //если элемент успешно добалвен, то Add() вернет ид элемента, а если произошла ошибка то вернет false, в LAST_ERROR бдут содержаться текст ошибки;
                    echo "Добавлен элемент с ID : " . $PRODUCT_ID . "<br>";
                } else {
                    echo "Error: " . $this->el->LAST_ERROR . '<br>';
                }
            }
            fclose($handle);
        }
    }
}